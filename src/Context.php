<?php

namespace DigraphCMS;

use DigraphCMS\Content\Page;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\Response;
use Flatrr\SelfReferencingFlatArray;
use Throwable;

class Context
{
    protected static $context = [];

    public static function arg(string $key)
    {
        if (static::request()) {
            return @static::request()->url()->arg($key);
        } else {
            return null;
        }
    }

    public static function error(int $code)
    {
        static::response(Digraph::errorResponse(404));
    }

    public static function fields(): SelfReferencingFlatArray
    {
        if (!static::data('fields')) {
            static::data(
                'fields',
                new SelfReferencingFlatArray(
                    Config::get('templates.fields')
                )
            );
        }
        return static::data('fields');
    }

    public static function request(Request $request = null): ?Request
    {
        return static::data('request', $request);
    }

    public static function response(Response $response = null): ?Response
    {
        return static::data('response', $response);
    }

    public static function page(Page $page = null): ?Page
    {
        return static::data('page', $page);
    }

    public static function thrown(Throwable $thrown = null): ?Throwable
    {
        return static::data('thrown', $thrown);
    }

    public static function data($name, $value = null)
    {
        if (!static::$context) {
            return null;
        }
        end(static::$context);
        $context = &static::$context[key(static::$context)];
        if ($value !== null) {
            $context[$name] = $value;
        }
        return @$context[$name];
    }

    public static function begin()
    {
        static::$context[] = [];
    }

    public static function clone()
    {
        static::$context[] = end(static::$context) ?? [];
    }

    public static function end()
    {
        array_pop(static::$context);
    }
}
