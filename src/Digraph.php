<?php

namespace DigraphCMS;

use DigraphCMS\Cache\UserCacheNamespace;
use DigraphCMS\Content\Page;
use DigraphCMS\Content\Router;
use DigraphCMS\Content\Pages;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\RequestHeaders;
use DigraphCMS\HTTP\Response;
use DigraphCMS\UI\Templates;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\Permissions;
use Mimey\MimeTypes;
use Throwable;

// register core event subscriber
Dispatcher::addSubscriber(CoreEventSubscriber::class);

class Digraph
{
    const UUIDCHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Generate a response from an automatically-loaded request and render it.
     * In many cases once your config and database are configured calling this
     * is all that's necessary.
     *
     * @return void
     */
    public static function renderActualRequest(): void
    {
        static::makeResponse(static::actualRequest());
        Context::response()->renderHeaders();
        header('Content-Type: ' . static::inferMime());
        header('Content-Disposition: filename="' . static::inferFilename() . '"');
        Context::response()->renderContent();
    }

    protected static function inferMime(): string
    {
        if (Context::response()->mime()) {
            return Context::response()->mime();
        }
        return (new MimeTypes())->getMimeType(
            strtolower(pathinfo(static::inferFilename(), PATHINFO_EXTENSION))
        ) ?? 'text/html';
    }

    protected static function inferFilename(): string
    {
        if (Context::response()->filename()) {
            return Context::response()->filename();
        }
        return Context::url()->file() ?? 'index.html';
    }

    /**
     * Generate a random UUID. Can also be seeded with a string to use MD5 for
     * generating the result. This isn't something you should rely on as a
     * secure hash, but can be useful if for some reason you need to generate
     * UUIDs in a deterministic way.
     * 
     * Note that Digraph's UUIDs are not compatible with the GUID spec, as they
     * use characters a-z and A-Z, and not just a-f. This gives us more entropy, 
     * and has no performance implications as they are saved in the database as
     * strings anyway, and no binary/numeric operations are ever needed.
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
                    function (int $chars): string {
                        $string = '';
                        for ($i = 0; $i < $chars; $i++) {
                            $string .= substr(static::UUIDCHARS, random_int(0, strlen(static::UUIDCHARS) - 1), 1);
                        }
                        return $string;
                    },
                    [8, 4, 4, 4, 12]
                )
            );
        }
    }

    /**
     * Determine whether a string is a valid UUID, in terms of length and basic
     * composition (character types, placement of dashes, etc).
     *
     * @param string $uuid
     * @return boolean
     */
    public static function validateUUID(string $uuid): bool
    {
        return preg_match(
            '/^[' . static::UUIDCHARS . ']{8}\-([' . static::UUIDCHARS . ']{4}\-){3}[' . static::UUIDCHARS . ']{12}$/',
            $uuid
        );
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
            new RequestHeaders(getallheaders()),
            $_POST
        );
    }

    public static function makeResponse(Request $request)
    {
        ob_start();
        URLs::beginContext($request->url());
        Context::begin();
        Context::url($request->url())->normalize();
        Context::request($request);
        Context::response(new Response());
        // redirect if normalizing changed URL
        if ($request->url()->__toString() != $request->originalUrl()->__toString()) {
            Context::response()->redirect($request->url());
            return;
        }
        try {
            if (Permissions::url(Context::url()) === false) {
                throw new AccessDeniedError('');
            }
            // search for relevant pages and handle putting them into Context
            if (!$request->url()->explicitlyStaticRoute() && $pages = Pages::getAll($request->url()->route())) {
                // this route relates to one or more pages
                $route = $request->url()->route();
                $action = $request->url()->action();
                if (count($pages) == 1 && !Router::staticRouteExists($route, $action)) {
                    // one page with no matching static route: put it in Context and build content
                    /** @var Page */
                    $page = reset($pages);
                    // first check if this is the page's actual preferred URL
                    $pageURL = $page->url($action, Context::url()->query());
                    if ($pageURL->__toString() != Context::url()->__toString()) {
                        throw new RedirectException($pageURL, false, true);
                    }
                    // build response content
                    Context::page($page);
                    static::buildResponseContent();
                } else {
                    // create a multiple options page if multiple pages or 1+ page and a static route exists
                    Context::data('300_pages', $pages);
                    if (Router::staticRouteExists($route, $action)) {
                        $staticUrl = new URL("/~$route/$action.html");
                        $staticUrl->query($request->url()->query());
                        $staticUrl->normalize();
                        Context::data('300_static', $staticUrl);
                    }
                    static::buildErrorContent(300);
                }
            } else {
                // this route does not relate to any pages
                // make sure context url is explicitly static
                if (!Context::url()->explicitlyStaticRoute()) {
                    Context::url()->path(
                        preg_replace('@^/([^~])@', '/~$1', Context::url()->path())
                    );
                }
                static::buildResponseContent();
            }
            // wrap with template (HTML only)
            if (static::inferMime() == 'text/html') {
                Templates::wrapResponse(Context::response());
            }
        } catch (Throwable $th) {
            // do shared tasks, then re-throw to do things with different types
            try {
                // discard output buffers that were open when thrown
                while (ob_get_status()['level'] > 1) {
                    ob_end_clean();
                }
                // set up template and add error CSS
                Theme::resetPage();
                Theme::resetTheme();
                Theme::addBlockingPageCss('/styles_fallback/*.css');
                Context::response()->resetTemplate();
                Context::thrown($th);
                // rethrow error
                throw $th;
            } catch (RedirectException $r) {
                // RedirectExceptions are used to allow exception handling that becomes a redirect
                Context::response()->redirect($r->url(), $r->permanent(), $r->preserveMethod());
            } catch (HttpError $error) {
                // generate exception-handling page
                static::buildErrorContent($error->status(), $error->getMessage());
                Templates::wrapResponse(Context::response());
            } catch (Throwable $th) {
                // generate a fallback exception handling error page
                if (!Dispatcher::firstValue('onException_' . substr(get_class($th), (strrpos(get_class($th), '\\') ?: -1) + 1), [$th])) {
                    if (!Dispatcher::firstValue('onException', [$th])) {
                        static::buildErrorContent(500.1);
                    }
                }
                Templates::wrapResponse(Context::response());
            }
        }
        ob_end_clean();
        // return
        $response = Context::response();
        Context::end();
        URLs::endContext();
        return $response;
    }

    protected static function buildResponseContent()
    {
        // check output cache
        if (Config::get('content_cache.enabled')) {
            $cache = new UserCacheNamespace('content_cache');
            $hash = md5(serialize(Context::request()));
            if ($cache->exists($hash) && !$cache->expired($hash)) {
                Context::response($cache->get($hash));
                return;
            }
            $time = microtime(true);
        } else {
            $cache = null;
        }
        // generate response
        if (!static::normalResponse()) {
            static::buildErrorContent(404);
        }
        // output caching: cache for response cacheTTL if generating content
        // took longer in ms than config content_cache.min_ms
        if ($cache && $ttl = Context::response()->cacheTTL()) {
            $time = round((microtime(true) - $time) * 1000);
            if ($time > Config::get('content_cache.min_ms')) {
                $cache->set($hash, Context::response(), $ttl);
            }
        }
    }

    protected static function normalResponse(): ?bool
    {
        if (Context::page()) {
            return static::doPageRoute(Context::page(), Context::url()->action());
        } else {
            return static::doStaticRoute(Context::url()->route(), Context::url()->action());
        }
    }

    protected static function doPageRoute(Page $page, string $action): ?bool
    {
        $content = Router::pageRoute($page, $action);
        if ($content !== null) {
            Context::response()->content($content);
            return true;
        }
        return null;
    }

    protected static function doStaticRoute(string $route, string $action): ?bool
    {
        $content = Router::staticRoute($route, $action);
        if ($content !== null) {
            Context::response()->content($content);
            return true;
        }
        return null;
    }

    public static function buildErrorContent(float $status, string $message = null): bool
    {
        Context::data('error_message', $message);
        Context::response()->status(floor($status));
        Context::response()->filename(floor($status) . '.html');
        $built =
            static::doStaticRoute('error', $status) ??
            static::doStaticRoute('error', round($status)) ??
            static::doStaticRoute('error', floor($status / 100) . 'xx') ??
            static::doStaticRoute('error', 'xxx');
        if (!$built) {
            throw new \Exception("Failed to build error content");
        }
        return true;
    }
}
