<?php

namespace DigraphCMS;

use DigraphCMS\Content\FilesystemRouter;
use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\Redirect;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\RequestHeaders;
use DigraphCMS\HTTP\Response;
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
        // redirect if URL has changed
        if ($request->url()->__toString() != $request->originalUrl()->__toString()) {
            $response = new Redirect($request->url());
        }
        // try to get a response
        $response = $response
            ?? static::doMakeResponse($request)
            ?? static::errorResponse(404, $request);
        // add canonical URL header if URL has changed now
        if ($request->url()->path() != $request->originalUrl()->path() || $request->url()->query() != $request->originalUrl()->query()) {
            $response->headers()->set('Link', '<' . $request->url() . '>; rel="canonical"');
        }
        // TODO: wrap with template
        // return
        URLs::endContext();
        return $response;
    }

    public static function doMakeResponse(Request $request): ?Response
    {
        $url = $request->url();
        $route = $url->route();
        $action = $url->action();
        if ($url->explicitlyStaticRoute() || ($pages = Pages::getAll($route))->count() == 0) {
            $url = $request->url();
            $url->path(preg_replace('@^/([^~][^\/]*/)@', '/~$1', $url->path()));
            $request->url($url);
            return
                Dispatcher::firstValue('onStaticRoute_' . $action, [$request, $route]) ??
                Dispatcher::firstValue('onStaticRoute', [$request, $route, $action]) ??
                static::staticRoute($request, $route, $action);
        } elseif ($pages->count() > 1 || FilesystemRouter::staticRouteExists($route, $action)) {
            return static::errorResponse(300, $request);
        } else {
            $page = $pages->fetch();
            $request->page($page);
            return
                Dispatcher::firstValue('onPageRoute_' . $action, [$request, $page]) ??
                Dispatcher::firstValue('onPageRoute', [$request, $page, $action]) ??
                static::pageRoute($request, $page, $action);
        }
    }

    protected static function pageRoute(Request $request, Page $page, string $action): ?Response
    {
        $response = new Response($request->url(), 200);
        $response->page($page);
        $content = FilesystemRouter::pageRoute($request, $response, $page, $action);
        if ($content !== null) {
            $response->content($content);
            return $response;
        }
        return null;
    }

    protected static function staticRoute(Request $request, string $route, string $action): ?Response
    {
        $response = new Response($request->url(), 200);
        $content = FilesystemRouter::staticRoute($request, $response, $route, $action);
        if ($content !== null) {
            $response->content($content);
            return $response;
        }
        return null;
    }

    /**
     * Create an error page Response for a given Request
     *
     * @param integer $status
     * @param Request $request
     * @return Response
     */
    public static function errorResponse(int $status, Request $request, array $data = []): Response
    {
        $errorURL = new URL("/~error/$status.html");
        return
            Dispatcher::firstValue('onErrorResponse', [$status, $request, $data]) ??
            static::staticRoute($request, $errorURL->route(), $status) ??
            static::staticRoute($request, $errorURL->route(), 'xxx') ??
            new Response(
                $request ? $request->url() : $errorURL,
                $status
            );
    }
}
