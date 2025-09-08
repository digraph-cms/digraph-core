<?php

namespace DigraphCMS\URL;

use DigraphCMS\Config;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\SafeContent\Sanitizer;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use Exception;

/**
 * A URL represents a URL within the site defined by URL::$siteHost and
 * URL::$sitePath, and including the in-site path and query parameters. A key
 * feature of URLs is that they can be printed as a string.
 */
class URL
{
    /** @var string|null */
    protected $name;
    /** @var string */
    protected $path = '';
    /** @var array<string,string> */
    protected $query = [];

    /**
     * Parse a string and attempt to turn it into an in-site URL. Accepts both
     * relative and leading-slash site-absolute URLs. Also accepts full query
     * strings like `?a=b` or partial ones like `&a=b`. Can also traverse using
     * `..` directory names, and such traversals will be kept within the site
     * root path.
     *
     * Also accepts full URLs, as long as they are in-site.
     *
     * ! An exception will be thrown if a not-in-site full URL is provided
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        // replace empty url with context
        if ($url == '') {
            $url = Context::url()->directory();
        }
        // prefix with context for empty or query-only strings
        if (!$url) {
            $url = Context::url()->__toString();
        }
        // set query for query-only string
        if ($url[0] == '?') {
            parse_str(substr($url, 1), $query);
            $url = clone Context::url();
            $url->query($query);
            $url = $url->__toString();
        }
        // merge in query for partial query strings
        if ($url[0] == '&') {
            parse_str(substr($url, 1), $query);
            $url = clone Context::url();
            $query = array_merge($url->query(), $query);
            $url->query($query);
            $url = $url->__toString();
        }
        // strip protocol
        $url = preg_replace('@^(https?:)?//@', '//', $url);
        // prefix with site URL if it starts with /
        if ($url[0] == '/' && @$url[1] != '/') {
            $url = URLs::site() . $url;
        } // otherwise add it to the context directory
        elseif ($url[0] != '/') {
            $url = URLs::site() . Context::url()->directory() . $url;
        }
        // strip protocol
        $url = preg_replace('@^(https?:)?//@', '//', $url);
        // parse
        $parsed = parse_url($url);
        // URL must be inside site
        $site = preg_replace('@^(https?:)?//@', '//', URLs::site());
        if (substr($url, 0, strlen($site)) != $site) {
            throw new Exception('Trying to parse URL ' . $url . ' failed, URLs must be in site ' . $site);
        }
        // pull out in-site path
        $this->path(substr($parsed['path'], strlen(URLs::sitePath())));
        // pull out query
        if (@$parsed['query']) {
            parse_str($parsed['query'], $this->query);
            ksort($this->query);
        } else {
            $this->query = [];
        }
    }

    /**
     * @param string      $source   the advertiser, site, publication, etc. that is sending traffic to your property,
     *                              for example: google, newsletter4, billboard.
     * @param string      $medium   advertising or marketing medium, for example: cpc, banner, email newsletter.
     * @param string      $campaign individual campaign name, slogan, promo code, etc. for a product.
     * @param string|null $term     Identify paid search keywords. If you're manually tagging paid keyword campaigns,
     *                              you should also use utm_term to specify the keyword.
     * @param string|null $content  Used to differentiate similar content, or links within the same ad. For example, if
     *                              you have two call-to-action links within the same email message, you can use
     *                              utm_content and set different values for each so you can tell which version is more
     *                              effective.
     *
     * @return static
     */
    public function utm(
        string $source,
        string $medium,
        string $campaign,
        string $term = null,
        string $content = null,
    ): static
    {
        $this->arg('utm_source', $source);
        $this->arg('utm_medium', $medium);
        $this->arg('utm_campaign', $campaign);
        if ($term) $this->arg('utm_term', $term);
        if ($content) $this->arg('utm_content', $content);
        return $this;
    }

    public function permissions(User $user = null): bool
    {
        return Permissions::url($this, $user);
    }

    public function parent(): ?URL
    {
        $parent =
            ($this->page() ? $this->page()->parent($this) : null) ??
            Dispatcher::firstValue('onStaticUrlParent_' . $this->route(), [$this]) ??
            Dispatcher::firstValue('onStaticUrlParent', [$this]) ??
            $this->hackUrlForParent();
        if ($parent->__toString() == $this->__toString()) {
            return null;
        } else {
            return $parent;
        }
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
    public function arg_string(string $key, bool $nullable = false): string|null
    {
        $value = $this->arg($key);
        if (is_null($value) && !$nullable) throw new HttpError(400, "Missing argument '$key'");
        if (is_null($value)) return null;
        $typed_value = strval($value);
        if ($value != $typed_value) throw new HttpError(400, "Invalid argument '$key'");
        return $typed_value;
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
    public function arg_int(string $key, bool $nullable = false): int|null
    {
        $value = $this->arg($key);
        if (is_null($value) && !$nullable) throw new HttpError(400, "Missing argument '$key'");
        if (is_null($value)) return null;
        $typed_value = intval($value);
        if ($value != $typed_value) throw new HttpError(400, "Invalid argument '$key'");
        return $typed_value;
    }

    /**
     * Get an argument and verify that it is a legitimate float, optionally allowing null values if the given arg is
     * not specified at all in the URL.
     *
     * @param string $key
     * @param bool   $nullable
     *
     * @return bool|null
     * @throws HttpError if the argument is not a valid boolean or is null and $nullable is false
     */
    public function arg_bool(string $key, bool $nullable = false): bool|null
    {
        $value = $this->arg($key);
        if (is_null($value) && !$nullable) throw new HttpError(400, "Missing argument '$key'");
        if ($value == '1') return true;
        elseif ($value == '0') return false;
        elseif (is_null($value)) return null;
        else throw new HttpError(400, "Invalid argument '$key'");
    }

    /**
     * @param array<mixed,string> $class
     * @param boolean             $inPageContext
     * @param string|null         $target
     *
     * @return string
     */
    public function html(array $class = [], bool $inPageContext = false, string $target = null): string
    {
        $normalized = clone($this);
        $normalized->normalize();
        if ($normalized->pathString() == Context::url()->pathString()) {
            $class[] = 'current-page';
        }
        if ($class) {
            $class = ' class="' . implode(' ', $class) . '"';
        } else {
            $class = '';
        }
        if ($target) {
            $target = ' data-target="' . $target . '"';
        }
        return "<a href=\"$normalized\"$class$target>" . Sanitizer::full($normalized->name($inPageContext)) . "</a>";
    }

    public function page(): ?AbstractPage
    {
        if ($this->explicitlyStaticRoute()) {
            return null;
        } else {
            return Pages::get($this->route());
        }
    }

    public function setName(string $name = null): static
    {
        $this->name = $name;
        return $this;
    }

    public function name(bool $inPageContext = false): string
    {
        // first return override name if possible
        if ($this->name) {
            return $this->name;
        }
        // then send out events and such
        if ($page = $this->page()) {
            // dispatch events to try and get url name by route classes of page
            foreach ($page->routeClasses() as $class) {
                if ($name = Dispatcher::firstValue("onPageUrlName_$class", [$this, $inPageContext])) {
                    return $name;
                }
            }
            // fall back to generic event, page title(), and then path
            return
                Dispatcher::firstValue('onPageUrlName', [$this, $inPageContext]) ??
                $this->page()->title($this, $inPageContext) ??
                ($this->action() == 'index' ? URLs::pathToName($this->path(), $inPageContext) : URLs::pathtoName($this->action(), $inPageContext));
        } else {
            // look for static route names by route-specific events, then generic, then fall back to path
            return
                Dispatcher::firstValue('onStaticUrlName_' . $this->route(), [$this, $inPageContext]) ??
                Dispatcher::firstValue('onStaticUrlName', [$this, $inPageContext]) ??
                ($this->action() == 'index' ? URLs::pathToName($this->path(), $inPageContext) : URLs::pathtoName($this->action(), $inPageContext));
        }
    }

    public function normalize(): static
    {
        // key sort query arguments
        ksort($this->query);
        // strip trailing index.html
        $this->path = preg_replace('@/index\.html$@', '/', $this->path);
        // ensure path ends in either a slash or file extension
        if (!preg_match('@(/|\.([a-z0-9]+))$@', $this->path) && !strpos($this->file(), ':')) {
            $this->path .= '/';
        }
        return $this;
    }

    /**
     * Get the directory portion of the URL path, without any trailing filename
     * but with a trailing slash.
     *
     * @return string
     */
    public function directory(): string
    {
        $path = $this->path();
        if (substr($path, -1) !== '/') {
            $path = dirname($path);
            if ($path !== '/') {
                $path .= '/';
            }
            return $path;
        }
        return $path;
    }

    /**
     * Get the trailing filename, returns null if nothing specified
     *
     * @return string
     */
    public function file(): ?string
    {
        $url = clone $this;
        $url->query([]);
        if (!preg_match('@/$@', $url->path())) {
            return basename($url->path());
        }
        return null;
    }

    public function route(): string
    {
        $route = trim($this->directory(), '/');
        if (substr($route, 0, 1) == '~') {
            $route = substr($route, 1);
        }
        if ($route == '' || $route == '/' || $route == '\\') {
            $route = 'home';
        }
        return $route;
    }

    /**
     * Return whether or not the current URL is an explicitly static route
     * (meaning that the base directory starts with "~")
     *
     * @return boolean
     */
    public function explicitlyStaticRoute(): bool
    {
        return substr($this->directory(), 0, 2) === '/~';
    }

    /**
     * Get the name of the "action" to be used for routing purposes
     *
     * @return string
     */
    public function action(): string
    {
        if ($file = $this->file()) {
            return preg_replace('/\.html$/', '', $file);
        }
        return 'index';
    }

    /**
     * Set/change the action of this URL, appending .html automatically if necessary
     *
     * @param string $action
     *
     * @return static
     */
    public function setAction(string $action): static
    {
        $this->path = substr($this->path, 0, strlen($this->path) - strlen($this->file() ?? ''));
        if (!preg_match('@(/|\.([a-z0-9]+))$@', $action) && !strpos($action, ':')) $action .= '.html';
        $this->path .= $action;
        return $this;
    }

    public function actionPrefix(): ?string
    {
        $action = $this->action();
        if ($pos = strpos($action, ':')) {
            return substr($action, 0, $pos);
        }
        return null;
    }

    public function actionSuffix(): ?string
    {
        $action = $this->action();
        if ($pos = strpos($action, ':')) {
            return substr($action, $pos + 1);
        }
        return null;
    }

    public function pathString(): string
    {
        // strip trailing index.html
        $path = preg_replace('@/index.html$@', '/', $this->path());
        // strip leading ~home or home directory
        $path = preg_replace('@^/~?home/@', '/', $path);
        return $path;
    }

    public function __toString(): string
    {
        return URLs::site() . $this->pathString() . $this->queryString();
    }

    public function queryString(): string
    {
        if ($this->query) {
            return '?' . http_build_query($this->query);
        } else {
            return '';
        }
    }

    public function fullPathString(): string
    {
        return $this->pathString() . $this->queryString();
    }

    /**
     * Get or set the in-site path of this URL
     *
     * @param string $path
     *
     * @return string
     */
    public function path(string $path = null): string
    {
        if ($path !== null) {
            // make sure trailing .. has a trailing slash
            $path = preg_replace('@/\.\.$@', '/../', $path);
            // resolve ..s
            $path = explode('/', $path);
            foreach ($path as $i => $e) {
                if ($e === '..') {
                    $path[$i] = false;
                    while ($i >= 0) {
                        if ($path[$i] !== false) {
                            $path[$i] = false;
                            break;
                        } else {
                            $i--;
                        }
                    }
                }
            }
            $path = array_filter($path, function ($e) {
                return $e !== false;
            });
            $path = implode('/', $path);
            // normalize to have leading slash
            if (@$path[0] != '/') {
                $path = '/' . $path;
            }
            // save
            $this->path = $path;
        }
        return $this->path;
    }

    /**
     * Get or set the entire query string as an array
     *
     * @param array<string,string> $query
     *
     * @return array<string,string>
     *
     * @throws HttpError on set if any argument values are suspicious/unsafe
     */
    public function query(array $query = null): array
    {
        if ($query !== null) {
            foreach ($query as $key => $value) {
                $this->arg($key, $value);
            }
        }
        return $this->query;
    }

    /**
     * Get or set a single arg in the query. Setting will normalize the order
     * of query arguments.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return string|null
     *
     * @throws HttpError on set if the value is suspicious/unsafe
     */
    public function arg(string $name, mixed $value = null): ?string
    {
        if ($value !== null) {
            // make sure there's nothing untoward in the value
            if ($value instanceof \Stringable) $value = strval($value);
            $is_string = is_string($value);
            $is_int = is_int($value);
            $is_float = is_float($value);
            $is_bool = is_bool($value);
            if (!$is_string && !$is_int && !$is_float && !$is_bool) throw new HttpError(400, "Invalid argument value");
            if ($is_string) {
                // if it's a string, sanitize it and see if it changed, if so it was suspicious or invalid
                $sanitized = trim($value);
                $sanitized = filter_var($sanitized, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
                if ($sanitized != $value) throw new HttpError(400, "Invalid argument value");
            } elseif ($is_bool) {
                // if it's a bool, set it to 1 or 0
                $value = $value ? '1' : '0';
            } else {
                // otherwise just string it
                $value = strval($value);
            }
            // set and sort the arguments
            $this->query[$name] = strval($value);
            ksort($this->query);
        }
        return @$this->query[$name];
    }

    /**
     * Unset an argument from the query.
     *
     * @param string $name
     *
     * @return static
     */
    public function unsetArg(string $name)
    {
        unset($this->query[$name]);
        return $this;
    }

    protected function hackUrlForParent(): ?URL
    {
        $parent = $this->pathString();
        while ($parent != 'home') {
            $parent = trim(dirname($parent), '/\\');
            if ($parent == '' || $parent == '.') {
                $home = new URL("/");
                $home->setName(Config::get('urls.home_name'));
                return $home;
            }
            if (Router::staticRouteExists($parent, 'index') || Pages::countAll($parent) > 0) {
                return new URL("/$parent/");
            }
        }
        return null;
    }
}