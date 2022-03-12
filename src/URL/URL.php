<?php

namespace DigraphCMS\URL;

use DigraphCMS\Config;
use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;

/**
 * A URL represents a URL within the site defined by URL::$siteHost and 
 * URL::$sitePath, and including the in-site path and query parameters. A key
 * feature of URLs is that they can be printed as a string.
 */
class URL
{
    protected $name;
    protected $path = '';
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
            $url = URLs::context()->directory();
        }
        // prefix with context for empty or query-only strings
        if (!$url || $url[0] == '?') {
            $url = URLs::context() . $url;
        }
        // merge in query for partial query strings
        if ($url[0] == '&') {
            parse_str(substr($url, 1), $query);
            $url = clone URLs::context();
            $query = array_merge($url->query(), $query);
            $url->query($query);
            $url = $url->__toString();
        }
        // strip protocol
        $url = preg_replace('@^(https?:)?//@', '//', $url);
        // prefix with site URL if it starts with /
        if ($url[0] == '/' && @$url[1] != '/') {
            $url = URLs::site() . $url;
        }
        // otherwise add it to the context directory
        elseif ($url[0] != '/') {
            $url = URLs::site() . URLs::context()->directory() . $url;
        }
        // strip protocol
        $url = preg_replace('@^(https?:)?//@', '//', $url);
        // parse
        $parsed = parse_url($url);
        // URL must be inside site
        $site = preg_replace('@^(https?:)?//@', '//', URLs::site());
        if (substr($url, 0, strlen($site)) != $site) {
            throw new \Exception('Trying to parse URL ' . $url . ' failed, URLs must be in site ' . $site);
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

    public function html(array $class = [], bool $inPageContext = false, string $target = null): string
    {
        $normalized = clone ($this);
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
        return "<a href=\"$normalized\"$class$target>" . $normalized->name($inPageContext) . "</a>";
    }

    public function page(): ?Page
    {
        if ($this->explicitlyStaticRoute()) {
            return null;
        } else {
            return Pages::get($this->route());
        }
    }

    public function setName(string $name = null)
    {
        $this->name = $name;
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

    public function normalize()
    {
        // key sort query arguments
        ksort($this->query);
        // strip trailing index.html
        $this->path = preg_replace('@/index\.html$@', '/', $this->path);
        // ensure path ends in either a slash or file extension
        if (!preg_match('@(/|\.([a-z0-9]+))$@', $this->path)) {
            $this->path .= '/';
        }
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

    protected function queryString(): string
    {
        if ($this->query) {
            return '?' . http_build_query($this->query);
        } else {
            return '';
        }
    }

    /**
     * Get or set the in-site path of this URL
     *
     * @param string $path
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
     * @param array $query
     * @return array
     */
    public function query(array $query = null): array
    {
        if ($query !== null) {
            $this->query = $query;
        }
        return $this->query;
    }

    /**
     * Get or set a single arg in the query. Setting will normalize the order
     * of query arguments.
     *
     * @param string $name
     * @param string $value
     * @return string|null
     */
    public function arg(string $name, string $value = null): ?string
    {
        if ($value !== null) {
            $this->query[$name] = $value;
            ksort($this->query);
        }
        return @$this->query[$name];
    }

    /**
     * Unset an argument from the query.
     *
     * @param string $name
     * @return void
     */
    public function unsetArg(string $name)
    {
        unset($this->query[$name]);
    }
}
