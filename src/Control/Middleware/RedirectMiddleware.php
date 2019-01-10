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
        if (strpos($request->getURL(), 'dev') === 0) {
            $response = $delegate($request);
            return $response;
        }

        $reserved_chars = ['{', '}', '(', ')', '/', '\\', '@', ':'];

        $cache = Injector::inst()->get(CacheInterface::class . '.redirectMiddlewareCache');
        $url = str_replace(
            $reserved_chars,
            array_fill(0, count($reserved_chars), '^'),
            strtolower($request->getURL())
        );

        // Handle homepage
        if ($url == '') {
            $url = 'home';
        }

        if ($cache->has($url)) {
            $val = explode('^', $cache->get($url));

            $response = HTTPResponse::create();
            $response = $response->redirect($val[0], $val[1] ?: 302);
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
