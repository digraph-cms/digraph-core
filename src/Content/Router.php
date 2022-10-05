<?php

namespace DigraphCMS\Content;

use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\URL\URL;

// Always add the default system routes directory
Router::addSource(__DIR__ . '/../../routes');

class Router
{
    protected static $sources = [];

    /**
     * Get a list of all the actions available for the given page, optionally filtering non-menu
     * actions, which are by default those that begin with an underscore.
     * 
     * Actions are also always filtered by the current user's permissions.
     *
     * @param AbstractPage $page
     * @param boolean $menuFilter
     * @return array
     */
    public static function pageActions(AbstractPage $page, bool $menuFilter = false): array
    {
        $urls = [];
        foreach ($page->routeClasses() as $c) {
            foreach (self::search("@$c/*.action.*") as $file) {
                $action = basename($file);
                $action = preg_replace('/\.action\..+$/', '', $action);
                if (substr($action, 0, 9) == '@wildcard' || $action == 'index') {
                    continue;
                }
                if (!preg_match('/\.[a-z0-9]+$/', $action)) {
                    $urls[] = $page->url($action);
                }
            }
        }
        Dispatcher::dispatchEvent('onPageActions', [&$urls]);
        foreach ($page->routeClasses() as $c) {
            Dispatcher::dispatchEvent('onPageActions_' . $c, [&$urls]);
        }
        return static::filterActions($urls, $menuFilter);
    }

    /**
     * Get a list of all the static actions available for the given route, optionally filtering
     * non-menu actions, which are by default those that begin with an underscore.
     * 
     * Actions are also always filtered by the current user's permissions.
     *
     * @param string $route
     * @param boolean $menuFilter
     * @return URL[]
     */
    public static function staticActions(string $route, bool $menuFilter = false): array
    {
        $urls = [];
        foreach (self::search("~$route/*/index.action.*") as $file) {
            $action = basename(dirname($file));
            $urls[] = new URL("/~$route/$action/");
        }
        foreach (self::search("~$route/*.action.*") as $file) {
            $action = basename($file);
            $action = preg_replace('/\.action\..+$/', '', $action);
            if (substr($action, 0, 9) == '@wildcard' || $action == 'index') {
                continue;
            }
            if (!preg_match('/\.[a-z0-9]+$/', $action)) {
                $urls[] = new URL("/~$route/$action.html");
            }
        }
        Dispatcher::dispatchEvent('onStaticActions', [&$urls]);
        Dispatcher::dispatchEvent('onStaticActions_' . $route, [&$urls]);
        return static::filterActions($urls, $menuFilter);
    }

    /**
     * Do the final filtering of a list of actions. This removes any non-menu actions. By default
     * this is any with actions that begin with an underscore, but this is extensible with the
     * Dispatcher event `onFilterActions(URL $url): ?bool`
     *
     * @param URL[] $urls
     * @param boolean $menuFilter
     * @return URL[]
     */
    protected static function filterActions(array $urls, bool $menuFilter): array
    {
        return array_unique(array_filter(
            $urls,
            function (URL $url) use ($menuFilter) {
                if ($menuFilter) {
                    if (($result = Dispatcher::firstValue('onFilterActions', [$url])) !== null) return $result;
                    if (substr($url->action(), 0, 1) == '_') return false;
                }
                return $url->permissions();
            }
        ));
    }

    public static function search(string $glob): array
    {
        $files = [];
        foreach (static::$sources as $source) {
            foreach (glob("$source/$glob") as $file) {
                if (is_file($file)) {
                    $files[] = $file;
                }
            }
        }
        return $files;
    }

    public static function include(string $glob)
    {
        if (substr($glob, 0, 1) != '/') {
            $route = Context::url()->route();
            if (!Context::page()) {
                $route = preg_replace('@^([^~])@', '~$1', $route);
            }
            $glob = "/$route/$glob";
        }
        foreach (static::$sources as $source) {
            foreach (glob("$source$glob") as $file) {
                if (is_file($file)) {
                    echo require_file($file);
                }
            }
        }
    }

    /**
     * Add a source directory to the top of the list of directories to search in
     * for filesystem route handlers. 
     *
     * @param string $dir
     * @return void
     */
    public static function addSource(string $dir)
    {
        if (($dir = realpath($dir)) && is_dir($dir) && !in_array($dir, self::$sources)) {
            array_unshift(self::$sources, $dir);
        }
    }

    /**
     * Search for and execute a route handler for a given page and action.
     */
    public static function pageRoute(AbstractPage $page, string $action)
    {
        // try specific routes first
        foreach ($page->routeClasses() as $c) {
            $output = self::tryRoute("@$c/$action");
            if (trim($output ?? '')) return $output;
        }
        // check if there's a prefix
        if ($pos = strpos($action, '_')) {
            $prefix = substr($action, 0, $pos);
            // try prefix wildcard route
            foreach ($page->routeClasses() as $c) {
                $output = self::tryRoute("@$c/@wildcard_$prefix");
                if (trim($output ?? '')) return $output;
            }
        }
        // try wildcard routes last
        foreach ($page->routeClasses() as $c) {
            $output = self::tryRoute("@$c/@wildcard");
            if (trim($output ?? '')) return $output;
        }
        return null;
    }

    /**
     * Check whether a handler for a given page route exists, but do not
     * actually execute it yet.
     *
     * @param AbstractPage $page
     * @param string $action
     * @return boolean
     */
    public static function pageRouteExists(AbstractPage $page, string $action): bool
    {
        foreach (self::$sources as $source) {
            foreach ($page->routeClasses() as $route) {
                $path = "$source/@$route/$action.action.*";
                if (glob($path)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Search for and execute a static route for a given route and action. 
     */
    public static function staticRoute(string $route, string $action)
    {
        $route = preg_replace('/^~/', '', $route);
        // try specific route
        $output = self::tryRoute("~$route/$action");
        if (trim($output ?? '')) {
            return $output;
        }
        // check if there's a prefix
        if ($pos = strpos($action, '_')) {
            $prefix = substr($action, 0, $pos);
            // try prefix wildcard route
            $output = self::tryRoute("~$route/@wildcard_$prefix");
            if (trim($output ?? '')) {
                return $output;
            }
        }
        // try wildcard route
        $output = self::tryRoute("~$route/@wildcard");
        if (trim($output ?? '')) {
            return $output;
        }
        return null;
    }

    /**
     * Check whether a handler for a given static route exists, but do not
     * actually execute it yet.
     *
     * @param string $route
     * @param string $action
     * @return boolean
     */
    public static function staticRouteExists(string $route, string $action): bool
    {
        $route = preg_replace('/^~/', '', $route);
        foreach (self::$sources as $source) {
            // check individual/specific route
            $path = "$source/~$route/$action.action.*";
            foreach (glob($path) as $file) {
                if (is_file($file)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected static function tryRoute(string $route)
    {
        foreach (self::$sources as $source) {
            $paths = glob("$source/$route.action.*");
            foreach ($paths as $path) {
                if (is_file($path)) {
                    $extension = pathinfo($path, PATHINFO_EXTENSION);
                    if (is_string($content = Dispatcher::firstValue('onRenderRoute_' . $extension, [$path, $route]))) {
                        return $content;
                    }
                }
            }
        }
        return null;
    }
}

function require_file(string $file)
{
    ob_start();
    try {
        require $file;
    } catch (\Throwable $th) {
        ob_end_clean();
        throw $th;
    }
    return ob_get_clean();
}
