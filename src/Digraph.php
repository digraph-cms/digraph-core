<?php

namespace DigraphCMS;

use DigraphCMS\Content\Router;
use DigraphCMS\Content\Pages;
use DigraphCMS\HTTP\Redirect;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\RequestHeaders;
use DigraphCMS\HTTP\Response;
use DigraphCMS\Templates\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;

class Digraph
{
    /**
     * Generate a response from an automatically-loaded request and render it.
     * In many cases once your config and database are configured calling this
     * is all that's necessary.
     *
     * @return void
     */
    public static function renderActualRequest(): void
    {
        $response = static::makeResponse(static::actualRequest());
        $response->render();
    }

    /**
     * Generate a random UUID. Can also be seeded with a string to use MD5 for
     * generating the result. This isn't something you should rely on as a
     * secure hash, but can be useful if for some reason you need to generate
     * UUIDs in a deterministic way.
     *
     * @param string $seed
     * @return string
     */
    public static function uuid(string $seed = null): string
    {
        if ($seed) {
            $hash = md5($seed);
            return implode(
                '-',
                [
                    substr($hash, 0, 8),
                    substr($hash, 8, 4),
                    substr($hash, 12, 4),
                    substr($hash, 16, 4),
                    substr($hash, 20),
                ]
            );
        } else {
            return implode(
                '-',
                array_map(
                    'bin2hex',
                    array_map(
                        'random_bytes',
                        [4, 2, 2, 2, 6]
                    )
                )
            );
        }
    }

    /**
     * Get the current actual URL as a URL object
     *
     * @return URL
     */
    public static function actualUrl(): URL
    {
        $url = new URL('//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $url->query($_GET);
        return $url;
    }

    /**
     * Get the current actual request as a Request object, which will contain
     * the URL, method, and request headers.
     *
     * @return Request
     */
    public static function actualRequest(): Request
    {
        return new Request(
            static::actualUrl(),
            $_SERVER['REQUEST_METHOD'],
            new RequestHeaders(getallheaders())
        );
    }

    /**
     * Make a Response from a Request. Responses include everything necessary to
     * render output: headers, content, status, and URL
     *
     * @param Request $request
     * @return Response
     */
    public static function makeResponse(Request $request): Response
    {
        URLs::beginContext($request->url());
        Context::begin();
        Context::request($request);
        try {
            // redirect if URL has changed
            if ($request->url()->__toString() != $request->originalUrl()->__toString()) {
                return Context::response(new Redirect($request->url()));
            }
            // try to build a response
            Context::response(
                static::doMakeResponse()
                    ?? static::errorResponse(404)
            );
            // change response URL to static if status is 200 and no page is associated
            // this will cause a canonical header to be added next
            if (Context::response()->status() == 200 && !Context::page()) {
                $url = Context::response()->url();
                $url->path(preg_replace('@^/([^~][^\/]*/)@', '/~$1', $url->path()));
                Context::response()->url($url);
            }
            // if response URL doesn't match original URL, set canonical header
            if ($request->originalUrl() != Context::response()->url()) {
                Context::response()->headers()->set('Link', '<' . Context::response()->url() . '>; rel="canonical"');
            }
            // wrap with template (HTML only)
            if (Context::response()->mime() == 'text/html') {
                Templates::wrapResponse(Context::response());
            }
        } catch (\Throwable $th) {
            // generate an exception handling error page
            Context::thrown($th);
            Context::response(static::errorResponse(500, $request));
            Context::response()->resetTemplate();
            Templates::wrapResponse(Context::response());
        }
        // return
        $response = Context::response();
        Context::end();
        URLs::endContext();
        return $response;
    }

    protected static function doMakeResponse(): ?Response
    {
        $request = Context::request();
        $url = $request->url();
        $route = $url->route();
        $action = $url->action();
        if ($url->explicitlyStaticRoute() || ($pages = Pages::getAll($route))->count() == 0) {
            // run static routing if URL is explicitly static or no pages were found
            return static::doStaticRoute($route, $action);
        } elseif ($pages->count() > 1 || Router::staticRouteExists($route, $action)) {
            // create a multiple options page if multiple pages or 1+ page and a static route exist
            Context::data('300_pages', $pages);
            if (Router::staticRouteExists($route, $action)) {
                $staticUrl = new URL("/~$route/$action.html");
                $staticUrl->query($url->query());
                $staticUrl->normalize();
                Context::data('300_static', $staticUrl);
            }
            return static::errorResponse(300);
        } else {
            // run page routing if a single page was found, and no static routes
            Context::page($pages->fetch());
            return static::doPageRoute($action);
        }
    }

    protected static function doPageRoute(string $action): ?Response
    {
        Context::response(new Response(Context::request()->url(), 200));
        $content = Router::pageRoute(Context::page(), $action);
        if ($content === null) {
            return null;
        } elseif (is_string($content)) {
            Context::response()->content($content);
            return Context::response();
        } elseif ($content instanceof Response) {
            Context::response($content);
            return Context::response();
        } else {
            return null;
        }
    }

    protected static function doStaticRoute(string $route, string $action): ?Response
    {
        Context::response(new Response(Context::request()->url(), 200));
        $content = Router::staticRoute($route, $action);
        if ($content !== null) {
            Context::response()->content($content);
            return Context::response();
        }
        return null;
    }

    public static function errorResponse(int $status): Response
    {
        $response =
            static::doStaticRoute('error', $status) ??
            static::doStaticRoute('error', floor($status / 100) . 'xx') ??
            static::doStaticRoute('error', 'xxx') ??
            false;
        if (!$response) {
            $response = new Response(clone Context::request()->url(), $status);
            $response->content("<h1>Error: $status</h1><p>Additionally, no error response was correctly generated.</p>");
        } else {
            $response->status($status);
        }
        return $response;
    }
}
