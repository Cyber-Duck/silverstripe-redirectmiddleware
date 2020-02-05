<?php

namespace EdgarIndustries\RedirectMiddleware;

use EdgarIndustries\RedirectMiddleware\Model\Redirect;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\Debug;

class RedirectMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        if (strpos($request->getURL(), 'dev') === 0) {
            $response = $delegate($request);
            return $response;
        }

        $reserved_chars = ['{', '}', '(', ')', '/', '\\', '@', ':'];
        $url = str_replace(
            $reserved_chars,
            array_fill(0, count($reserved_chars), '^'),
            strtolower($request->getURL())
        );
        $url = str_replace('^', '/', $url);
        $redirects = Redirect::get();
        foreach ($redirects as $redirect) {
            if (! ((empty($url) && $redirect->FromURL == "home") || $url == $redirect->FromURL)) {
                continue;
            }

            if ($redirect->ToPageID > 0) {
                $pageSiteTree = SiteTree::get_by_id($redirect->ToPageID);
                if ($pageSiteTree) {
                    $redirectToPath = $pageSiteTree->URLSegment;
                }
            }

            if (! empty($redirectToPath)) {
                return $this->buildRedirectResponse($redirectToPath, $redirect->Code);
            }
        }

        $response = $delegate($request);

        return $response;
    }

    private function buildRedirectResponse($redirectToPath, $code)
    {
        if (strpos($redirectToPath, 'http') == false and strpos($redirectToPath, 'www') == false and strpos($redirectToPath, 'ww2') == false
            and strpos($redirectToPath, '/') == false) {
            $redirectToPath = '/' . $redirectToPath;
        }
        $response = HTTPResponse::create();
        $response = $response->redirect($redirectToPath, $code);
        return $response;
    }
}
