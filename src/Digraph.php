<?php

namespace DigraphCMS;

use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\Redirect;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\RequestHeaders;
use DigraphCMS\HTTP\Response;

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
        $response = self::makeResponse(self::actualRequest());
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
            self::actualUrl(),
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
        URL::beginContext($request->url());
        Dispatcher::dispatchEvent('onMakeResponse', [$request]);
        // redirect if URL has changed
        if ($request->url() != $request->originalUrl()) {
            $response = new Redirect($request->url());
        }
        // try to get a response
        $response = $response
            ?? Dispatcher::firstValue('onRequest', [$request])
            ?? Dispatcher::firstValue('onRequest_' . $request->method(), [$request])
            ?? self::errorResponse(404, $request);
        // return
        URL::endContext();
        return $response;
    }

    /**
     * Create an error page Response for a given Request
     *
     * @param integer $status
     * @param Request $request
     * @return Response
     */
    public static function errorResponse(int $status, Request $request): Response
    {
        if ($response = Dispatcher::firstValue('onErrorResponse', [$status, $request])) {
            return $response;
        }
        return new Response(
            $request ? $request->url() : new URL("/error_$status/"),
            $status
        );
    }
}
