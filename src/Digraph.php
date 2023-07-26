<?php

namespace DigraphCMS;

use DigraphCMS\Cache\Locking;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Router;
use DigraphCMS\Content\Pages;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DBConnectionException;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\RequestHeaders;
use DigraphCMS\HTTP\Response;
use DigraphCMS\Search\Search;
use DigraphCMS\UI\Templates;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\Redirects;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use Mimey\MimeTypes;
use Throwable;

abstract class Digraph
{
    /**
     * The characters and pattern used to generate UUIDs can be modified in config
     * under uuid.chars and uuid.pattern, respectively. They should all be URL-safe
     * characters, slashes are probably ill-advised, and the total length of a
     * UUID needs to be 36 characters or less. This means it can be configured to
     * produce valid version 4 UUIDs, if desired. The default is shorter, uses
     * more characters, and yields less entropy (but still more than enough for
     * any website this system is capable of scaling up to).
     */
    const UUIDCHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    const UUIDPATTERN = '000000';
    const LONGUUIDPATTERN = '0000000000000000';

    /**
     * Generate a response from an automatically-loaded request and render it.
     * In many cases once your config and database are configured calling this
     * is all that's necessary.
     *
     * @return void
     */
    public static function renderActualRequest(): void
    {
        try {
            static::makeResponse(static::actualRequest());
            Context::response()->renderHeaders();
            header('Content-Type: ' . static::inferMime());
            header('Content-Disposition: filename="' . static::inferFilename() . '"');
            Context::response()->renderContent();
        }
        // last resort error message
        catch (Throwable $th) {
            ExceptionLog::log($th);
            http_response_code(500);
            echo Templates::fallbackError($th);
        }
    }

    public static function inferMime(Response $response = null): string
    {
        $response = $response ?? Context::response();
        if ($response->mime()) {
            return $response->mime();
        }
        return (new MimeTypes())->getMimeType(
            strtolower(pathinfo(static::inferFilename($response), PATHINFO_EXTENSION))
        ) ?? 'text/html';
    }

    public static function inferFilename(Response $response = null): string
    {
        $response = $response ?? Context::response();
        if ($response->filename()) {
            return $response->filename();
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
     * @param string|null $prefix
     * @param string|null $seed
     * @return string
     */
    public static function uuid(string $prefix = null, string $seed = null): string
    {
        if ($seed !== null) {
            mt_srand(crc32($seed));
            $fn = 'mt_rand';
        } else {
            $fn = 'random_int';
        }
        return ($prefix ? $prefix . '_' : '')
            . preg_replace_callback(
                '/0/',
                function () use ($fn) {
                    return substr(static::uuidChars(), $fn(0, strlen(static::uuidChars()) - 1), 1);
                },
                static::uuidPattern()
            );
    }

    /**
     * Generate a random long UUID. Can also be seeded with a string to use MD5 for
     * generating the result. This isn't something you should rely on as a
     * secure hash, but can be useful if for some reason you need to generate
     * UUIDs in a deterministic way.
     * 
     * Note that Digraph's UUIDs are not compatible with the GUID spec, as they
     * use characters a-z and A-Z, and not just a-f. This gives us more entropy, 
     * and has no performance implications as they are saved in the database as
     * strings anyway, and no binary/numeric operations are ever needed.
     *
     * @param string|null $prefix
     * @param string|null $seed
     * @return string
     */
    public static function longUUID(string $prefix = null, string $seed = null): string
    {
        if ($seed !== null) {
            mt_srand(crc32($seed));
            $fn = 'mt_rand';
        } else {
            $fn = 'random_int';
        }
        return ($prefix ? $prefix . '_' : '')
            . preg_replace_callback(
                '/0/',
                function () use ($fn) {
                    return substr(static::uuidChars(), $fn(0, strlen(static::uuidChars()) - 1), 1);
                },
                static::longUuidPattern()
            );
    }

    public static function uuidChars(): string
    {
        return Config::get('uuid.chars') ?? static::UUIDCHARS;
    }

    public static function uuidPattern(): string
    {
        return Config::get('uuid.pattern') ?? static::UUIDPATTERN;
    }

    public static function longUuidPattern(): string
    {
        return Config::get('uuid.longpattern') ?? static::LONGUUIDPATTERN;
    }

    /**
     * Determine whether a string is a valid UUID, in terms of length and basic
     * composition (character types, placement of dashes, etc).
     *
     * @param string $uuid
     * @param string|null $prefix
     * @return boolean
     */
    public static function validateUUID(string $uuid, string $prefix = null): bool
    {
        if ($prefix) {
            if (substr($uuid, 0, strlen($prefix) + 1) != $prefix . '_') return false;
        }
        $pattern = '/^([a-z]+_)?' . str_replace('0', '[' . static::uuidChars() . ']', preg_quote(static::uuidPattern(), '/')) . '$/';
        return preg_match($pattern, $uuid);
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

    public static function makeResponse(Request $request): void
    {
        ob_start();
        $request->url()->normalize();
        Context::begin();
        Context::url($request->url());
        Context::request($request);
        Context::response(new Response());
        // check if there are any redirects for the request URL and bounce if necessary
        if ($destination = Redirects::destination($request->url())) {
            Context::response()->redirect($destination, true, true);
            return;
        }
        // allow URL normalization hooks
        Context::url(Dispatcher::chainEvents('onNormalizeUrl', $request->url()));
        // redirect if normalizing changed URL
        if (Context::url()->__toString() != $request->originalUrl()->__toString()) {
            Context::response()->redirect($request->url(), true, true);
            return;
        }
        // begin full response building process
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
                    /** @var AbstractPage */
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
                        $staticUrl = (new URL("/~$route/"))->setAction($action);
                        $staticUrl->query($request->url()->query());
                        $staticUrl->normalize();
                        Context::data('300_static', $staticUrl);
                    }
                    static::buildErrorContent(300);
                }
            } else {
                // this route does not relate to any pages, so make it explicitly non-static for tidiness
                if (Context::url()->explicitlyStaticRoute()) {
                    $url = Context::url();
                    $url->path(
                        preg_replace('@^/~@', '/', Context::url()->path())
                    );
                    Context::url($url);
                    throw new RedirectException(Context::url());
                }
                static::buildResponseContent();
            }
            // do search indexing if necessary
            if (Context::response()->searchIndex()) {
                $id = 'response_index:' . Context::url()->fullPathString();
                if (Locking::lock($id, false, Config::get('search.response_index.interval'))) {
                    $content = Context::response()->content();
                    if (Context::fields()['page.name']) {
                        $name = strip_tags(Context::fields()['page.name']);
                    } elseif (preg_match("@<h1[^>]*>(.+?)</h1>@i", $content, $matches)) {
                        $name = trim(strip_tags($matches[1]));
                    } else {
                        $name = strip_tags(Context::url()->name());
                    }
                    $url = Context::url();
                    new DeferredJob(function () use ($content, $name, $url) {
                        Search::indexURL('response_index', $url, $name, $content);
                        return "Indexed response for " . $url;
                    }, 'response_index');
                }
            }
            // wrap with template (HTML only)
            if (static::inferMime() == 'text/html') {
                Templates::wrapResponse(Context::response());
            }
        } catch (DBConnectionException $ex) {
            ExceptionLog::log($ex);
            // generate a fallback error page for DB connection errors, we use the fallback template because a
            // broken db connection breaks a LOT of things
            Context::response()->template('fallback.php');
            Context::thrown($ex);
            static::buildErrorContent(500.2);
            Templates::wrapResponse(Context::response());
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
                try {
                    if ($error->status() >= 500) {
                        ExceptionLog::log($error);
                    }
                    static::buildErrorContent($error->status(), $error->getMessage());
                    Templates::wrapResponse(Context::response());
                } catch (RedirectException $r) {
                    // RedirectExceptions are used to allow exception handling that becomes a redirect
                    Context::response()->redirect($r->url(), $r->permanent(), $r->preserveMethod());
                }
            } catch (Throwable $th) {
                try {
                    // generate a fallback exception handling error page
                    if (!Dispatcher::firstValue('onException_' . substr(get_class($th), (strrpos(get_class($th), '\\') ?: -1) + 1), [$th])) {
                        if (!Dispatcher::firstValue('onException', [$th])) {
                            ExceptionLog::log($th);
                            static::buildErrorContent(500.1);
                        }
                    }
                    Context::response()->template('fallback.php');
                    Templates::wrapResponse(Context::response());
                } catch (RedirectException $r) {
                    // RedirectExceptions are used to allow exception handling that becomes a redirect
                    Context::response()->redirect($r->url(), $r->permanent(), $r->preserveMethod());
                }
            }
        }
        ob_end_clean();
        // finalize response and dispatch ready events
        $response = Context::response();
        Dispatcher::dispatchEvent('onResponseReady', [$response]);
        if (static::inferMime($response) == 'text/html') {
            Dispatcher::dispatchEvent('onResponseReady_html', [$response]);
        }
        Context::end();
    }

    protected static function buildResponseContent(): void
    {
        if ($response = Context::cache()->get('content_cache')) {
            Context::response($response);
            return;
        }
        if (!static::normalResponse()) {
            // generate error response and delete this URL from the search index
            static::buildErrorContent(404);
            Search::deleteURL(Context::url());
        }
        if (Context::response()->cacheTTL()) {
            Context::cache()->set(
                'content_cache',
                Context::response(),
                Context::response()->cacheTTL()
            );
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

    protected static function doPageRoute(AbstractPage $page, string $action): ?bool
    {
        $content = Router::pageRoute($page, $action);
        if (Context::response()->contentFile()) {
            return true;
        }
        if ($content !== null) {
            Context::response()->content($content);
            return true;
        }
        return null;
    }

    protected static function doStaticRoute(string $route, string $action): ?bool
    {
        $content = Router::staticRoute($route, $action);
        if (Context::response()->contentFile()) {
            return true;
        }
        if ($content !== null) {
            Context::response()->content($content);
            return true;
        }
        return null;
    }

    public static function buildErrorContent(float $status, string $message = null): bool
    {
        Context::data('error_message', $message);
        Context::response()->status(intval(floor($status)));
        Context::response()->filename(intval(floor($status)) . '.html');
        $built =
            static::doStaticRoute('error', strval($status)) ??
            static::doStaticRoute('error', strval(round($status))) ??
            static::doStaticRoute('error', floor($status / 100) . 'xx') ??
            static::doStaticRoute('error', 'xxx');
        if (!$built) {
            throw new \Exception("Failed to build error content");
        }
        return true;
    }
}