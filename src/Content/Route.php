<?php

namespace DigraphCMS\Content;

use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\Response;

/**
 * Sandbox/helper class for providing context to filesystem routing includes.
 * Another key function of this class is to provide a static interface for
 * getting the current request/page in a way that will allow proper IDE hinting
 * and whatnot.
 */
class Route
{
    protected static $context = [];

    public static function request(): ?Request
    {
        if ($context = end(self::$context)) {
            return $context['request'];
        } else {
            return null;
        }
    }

    public static function response(): ?Response
    {
        if ($context = end(self::$context)) {
            return $context['response'];
        } else {
            return null;
        }
    }

    public static function page(): ?Page
    {
        if ($context = end(self::$context)) {
            return $context['page'];
        } else {
            return null;
        }
    }

    public static function include_file(string $file, Request $request, Response $response, Page $page = null): string
    {
        self::beginContext($request, $response, $page);
        ob_start();
        include_file($file);
        self::endContext();
        return ob_get_clean();
    }

    protected static function beginContext(Request $request, Response $response, Page $page = null)
    {
        self::$context[] = [
            'request' => $request,
            'response' => $response,
            'page' => $page
        ];
    }

    protected static function endContext()
    {
        array_pop(self::$context);
    }
}

function include_file($file)
{
    include $file;
}
