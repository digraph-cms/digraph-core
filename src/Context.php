<?php

namespace DigraphCMS;

use DigraphCMS\Content\Page;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\Response;
use DigraphCMS\URL\URL;
use Flatrr\SelfReferencingFlatArray;
use Throwable;

class Context
{
    protected static $context = [];

    public static function disableCache()
    {
        static::response()->browserTTL(0);
        static::response()->cacheTTL(0);
        static::response()->staleTTL(0);
    }

    public static function url(URL $url = null): URL
    {
        if ($url) {
            static::request()->url($url);
        }
        return clone static::request()->url();
    }

    /**
     * Retrieve an arg from the request URL
     *
     * @param string $key
     * @return mixed
     */
    public static function arg(string $key)
    {
        if (static::request()) {
            return @static::request()->url()->arg($key);
        } else {
            return null;
        }
    }

    /**
     * Used to "correct" a url argument so that it will not exist in the
     * canonical link header.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function unsetArg(string $key)
    {
        if (static::response()) {
            $url = static::response()->url();
            $url->unsetArg($key);
            static::response()->url($url);
        } else {
            return null;
        }
    }

    /**
     * Used to "correct" a url argument so that it will be reflected in the
     * canonical link header.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function correctArg(string $key, $value)
    {
        if (static::response()) {
            $url = static::response()->url();
            $url->arg($key, $value);
            static::response()->url($url);
        } else {
            return null;
        }
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
        static::request(static::request() ? clone static::request() : null);
        static::response(static::response() ? clone static::response() : null);
    }

    public static function end()
    {
        array_pop(static::$context);
    }
}
