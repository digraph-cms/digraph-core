<?php

namespace DigraphCMS;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\Response;
use DigraphCMS\URL\URL;
use Flatrr\SelfReferencingFlatArray;
use Throwable;

class Context
{
    protected static $request, $response, $url, $thrown;
    protected static $data = [];

    public static function url(URL $url = null): URL
    {
        if ($url) {
            static::$url = $url;
        }
        return clone static::$url;
    }

    /**
     * Retrieve an arg from the request URL
     *
     * @param string $key
     * @return mixed
     */
    public static function arg(string $key)
    {
        if (static::$request) {
            return @static::$request->url()->arg($key);
        } else {
            return null;
        }
    }

    /**
     * Retrieve an arg from the request POST
     *
     * @param string $key
     * @return mixed
     */
    public static function post(string $key)
    {
        if (static::$request) {
            return @static::$request->post()[$key];
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
                    Config::get('fields')
                )
            );
        }
        return static::data('fields');
    }

    public static function request(Request $request = null): ?Request
    {
        if ($request) {
            static::$request = $request;
        }
        return static::$request;
    }

    public static function response(Response $response = null): ?Response
    {
        if ($response) {
            static::$response = $response;
        }
        return static::$response;
    }

    public static function page(AbstractPage $page = null): ?AbstractPage
    {
        return static::data('page', $page);
    }

    public static function pageUUID(): ?string
    {
        return static::page() ? static::page()->uuid() : null;
    }

    public static function thrown(Throwable $thrown = null): ?Throwable
    {
        if ($thrown) {
            static::$thrown = $thrown;
        }
        return static::$thrown;
    }

    public static function data($name, $value = null)
    {
        end(static::$data);
        $endKey = key(static::$data);
        if ($value !== null) {
            static::$data[$endKey][$name] = $value;
        }
        return @static::$data[$endKey][$name];
    }

    public static function begin()
    {
        static::$data[] = [];
    }

    public static function clone()
    {
        static::$data[] = end(static::$data) ?? [];
    }

    public static function end()
    {
        array_pop(static::$data);
    }
}
