<?php

namespace DigraphCMS;

use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\Response;
use DigraphCMS\URL\URL;
use Flatrr\SelfReferencingFlatArray;
use Throwable;

abstract class Context
{
    /** @var Request|null */
    protected static $request;
    /** @var Response|null */
    protected static $response;
    /** @var Throwable|null */
    protected static $thrown;
    /** @var array<int,array<string|int,mixed>> */
    protected static $data = [];

    public static function beginEmail(): void
    {
        static::beginSimplifiedRendering(intval(Config::get('email.body_width')));
    }

    public static function beginSimplifiedRendering(int $width): void
    {
        static::begin();
        static::fields()['simplified_rendering'] = [
            'active' => true,
            'width' => $width
        ];
    }

    public static function ensureUUIDArg(string $checkWith = null): void
    {
        // ensure arg exists
        if (!static::arg('uuid')) {
            $url = Context::url();
            $url->arg('uuid', Digraph::uuid());
            throw new RedirectException($url);
        }
        // validate UUID
        if (!Digraph::validateUUID(Context::arg('uuid') ?? '')) {
            $url = Context::url();
            $url->arg('uuid', Digraph::uuid());
            throw new RedirectException($url);
        }
        // check with passed-in class
        if ($checkWith) {
            if ($checkWith::exists(Context::arg('uuid'))) {
                $url = Context::url();
                $url->arg('uuid', Digraph::uuid());
                throw new RedirectException($url);
            }
        }
    }

    /**
     * Get a cache namespace specific to the current request hash
     *
     * @param string|null $section
     * @return CacheNamespace
     */
    public static function cache(string $section = null): CacheNamespace
    {
        if (static::request()) $namespace = 'context/' . substr(static::request()->hash(), 0, 2) . '/' . static::request()->hash();
        else $namespace = 'context/none';
        if ($section) $namespace .= "/$section";
        return new CacheNamespace($namespace);
    }

    public static function url(URL $url = null): URL
    {
        return clone (static::data('url', $url)
            ?? Digraph::actualUrl());
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

    public static function data(string $name, mixed $value = null): mixed
    {
        end(static::$data);
        /** @var int|null */
        $endKey = intval(key(static::$data));
        if ($value !== null) {
            if (!isset(static::$data[$endKey])) static::$data[$endKey] = [];
            static::$data[$endKey][$name] = $value;
        }
        return @static::$data[$endKey][$name];
    }

    /**
     * Begin a "context" which will be used when parsing partial URLs, such as
     * relative paths, or query-only URL strings. For example, setting the
     * context to `[site]/foo/bar?a=b` would allow tricks like:
     * 
     *  * `..` => `[site]/`
     *  * `?z=b` => `[site]/foo/bar?z=b`
     *  * `&z=b` => `[site]/foo/bar?a=b&z=b`
     *
     * @param URL $url
     * @return void
     */
    public static function beginUrlContext(URL $url): void
    {
        static::copy();
        static::url($url);
    }

    public static function beginPageContext(AbstractPage $page): void
    {
        static::copy();
        static::page($page);
        static::url($page->url());
    }

    public static function begin(): void
    {
        static::$data[] = [];
    }

    public static function copy(): void
    {
        static::$data[] = end(static::$data) ? end(static::$data) : [];
    }

    public static function end(): void
    {
        array_pop(static::$data);
    }

    public static function clear(): void
    {
        static::$data = [];
    }
}