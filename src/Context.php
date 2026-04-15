<?php

namespace DigraphCMS;

use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\Response;
use DigraphCMS\URL\URL;
use Flatrr\SelfReferencingFlatArray;
use Joby\Smol\Sentry\Inspector;
use Joby\Smol\Sentry\Sentry;
use Throwable;

class Context
{

    /** @var Request|null */
    protected static $request;

    /** @var Response|null */
    protected static $response;

    /** @var Throwable|null */
    protected static $thrown;

    /** @var array<int,array<string|int,mixed>> */
    protected static array $data = [];

    public static Sentry|null $sentry;

    public static Inspector|null $inspector;

    public static function sentry(): Sentry
    {
        return static::$sentry
            ??= static::defaultSentry();
    }

    public static function inspector(): Inspector
    {
        return static::$inspector
            ??= static::defaultInspector();
    }

    public static function beginEmail(): void
    {
        static::beginSimplifiedRendering(intval(Config::get('email.body_width')));
    }

    public static function beginSimplifiedRendering(int $width): void
    {
        static::copy();
        static::fields()['simplified_rendering'] = [
            'active' => true,
            'width'  => $width,
        ];
    }

    public static function ensureUUIDArg(string|null $checkWith = null): void
    {
        // ensure arg exists
        if (!static::arg_string('uuid', true)) {
            $url = Context::url();
            $url->setArg('uuid', Digraph::uuid());
            throw new RedirectException($url);
        }
        // validate UUID
        if (!Digraph::validateUUID(Context::arg_string('uuid'))) {
            $url = Context::url();
            $url->setArg('uuid', Digraph::uuid());
            throw new RedirectException($url);
        }
        // check with passed-in class
        if ($checkWith) {
            if ($checkWith::exists(Context::arg_string('uuid'))) {
                $url = Context::url();
                $url->setArg('uuid', Digraph::uuid());
                throw new RedirectException($url);
            }
        }
    }

    /**
     * Get a cache namespace specific to the current request hash
     *
     * @param string|null $section
     *
     * @return CacheNamespace
     */
    public static function cache(string|null $section = null): CacheNamespace
    {
        if (static::request())
            $namespace = 'context/' . substr(static::request()->hash(), 0, 2) . '/' . static::request()->hash();
        else
            $namespace = 'context/none';
        if ($section)
            $namespace .= "/$section";
        return new CacheNamespace($namespace);
    }

    public static function url(URL|null $url = null): URL
    {
        return clone (static::data('url', $url)
            ?? Digraph::actualUrl());
    }

    /**
     * Retrieve an arg from the request URL
     *
     * @param string $key
     *
     * @return mixed
     *
     * @deprecated use one of the typed arg_*() methods instead
     */
    public static function arg(string $key): mixed
    {
        return static::$request?->url()->getArg($key, true);
    }

    /**
     * Get an argument and verify that it is a legitimate string, optionally allowing null values if the given arg is
     * not specified at all in the URL.
     *
     * @param string $key
     * @param bool   $nullable
     *
     * @return ($nullable is true ? string|null : string)
     *
     * @throws HttpError if the argument is not a valid string or is null and $nullable is false
     */
    public static function arg_string(string $key, bool $nullable = false): string|null
    {
        $value = @static::$request?->url()->arg_string($key, $nullable);
        if (is_null($value) && !$nullable)
            throw new HttpError(400, "Missing argument '$key'");
        return $value;
    }

    /**
     * Get an argument and verify that it is a legitimate integer, optionally allowing null values if the given arg is
     * not specified at all in the URL.
     *
     * @param string $key
     * @param bool   $nullable
     *
     * @return ($nullable is true ? int|null : int)
     *
     * @throws HttpError if the argument is not a valid integer or is null and $nullable is false
     */
    public static function arg_int(string $key, bool $nullable = false): int|null
    {
        $value = @static::$request?->url()->arg_int($key, $nullable);
        if (is_null($value) && !$nullable)
            throw new HttpError(400, "Missing argument '$key'");
        return $value;
    }

    /**
     * Get an argument and verify that it is a legitimate float, optionally allowing null values if the given arg is
     * not specified at all in the URL.
     *
     * @param string $key
     * @param bool   $nullable
     *
     * @return bool|null
     *
     * @throws HttpError if the argument is not a valid boolean or is null and $nullable is false
     */
    public static function arg_bool(string $key, bool $nullable = false): bool|null
    {
        $value = @static::$request?->url()->arg_bool($key, $nullable);
        if (is_null($value) && !$nullable)
            throw new HttpError(400, "Missing argument '$key'");
        return $value;
    }

    /**
     * Retrieve an arg from the request POST
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function post(string $key)
    {
        if (static::$request) {
            return @static::$request->post()[$key];
        }
        else {
            return null;
        }
    }

    public static function fields(): SelfReferencingFlatArray
    {
        if (!static::data('fields')) {
            static::data(
                'fields',
                new SelfReferencingFlatArray(
                    Config::get('fields'),
                ),
            );
        }
        return static::data('fields');
    }

    public static function request(Request|null $request = null): ?Request
    {
        if ($request) {
            static::$request = $request;
        }
        return static::$request;
    }

    public static function response(Response|null $response = null): ?Response
    {
        if ($response) {
            static::$response = $response;
        }
        return static::$response;
    }

    public static function page(AbstractPage|null $page = null): ?AbstractPage
    {
        return static::data('page', $page);
    }

    public static function pageUUID(): ?string
    {
        return static::page() ? static::page()->uuid() : null;
    }

    public static function thrown(Throwable|null $thrown = null): ?Throwable
    {
        if ($thrown) {
            static::$thrown = $thrown;
        }
        return static::$thrown;
    }

    public static function data(string $name, mixed $value = null): mixed
    {
        end(static::$data);
        $endKey = key(static::$data);
        if (!is_null($endKey))
            $endKey = (int) $endKey;
        if ($value !== null) {
            if (!isset(static::$data[$endKey]))
                static::$data[$endKey] = [];
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
     *
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

    protected static function defaultSentry(): Sentry
    {
        return Sentry::default(
            new \Joby\Smol\Query\DB(Config::get('sentry.db_file')),
            Config::get('sentry.abuseipdb_key'),
            Config::get('sentry.abuseipdb_daily_refreshes'),
        );
    }

    protected static function defaultInspector(): Inspector
    {
        return (new Inspector(static::sentry()))
            ->addRule('digraph', new SecurityInspector)
            ->addDefaultRules();
    }

}
