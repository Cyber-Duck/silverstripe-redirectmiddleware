<?php

namespace EdgarIndustries\RedirectMiddleware;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPRequest;

class RedirectMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.redirectMiddlewareCache');
        $url = str_replace('/', '^', $request->getURL());

        if ($cache->has($url)) {
            return new HTTPRequest('GET', $cache->get($url));
        }

        $response = $delegate($request);
        return $response;
    }
}
