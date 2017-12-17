<?php

namespace EdgarIndustries\RedirectMiddleware;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\Debug;

class RedirectMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.redirectMiddlewareCache');
        $url = str_replace('/', '^', strtolower($request->getURL()));

        // Handle homepage
        if ($url == '') {
            $url = 'home';
        }

        if ($cache->has($url)) {
            $response = HTTPResponse::create();
            $response = $response->redirect($cache->get($url));
        } else {
            $response = $delegate($request);
        }

        if ($request->getVar('debug_redirectmiddleware')) {
            echo '<h1>Debug RedirectMiddleware</h1>';

            echo '<h2>Requested URL</h2>';
            Debug::dump($request->getURL());

            echo '<h2>Matched cache key</h2>';
            if ($cache->has($url)) {
                Debug::dump([$url => $cache->get($url)]);
            } else {
                echo 'No match';
            }

            echo '<h2>Generated Response headers</h2>';
            Debug::dump($response->getStatusCode());
            Debug::dump($response->getHeaders());

            exit;
        }

        return $response;
    }
}
