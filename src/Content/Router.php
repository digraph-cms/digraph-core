<?php

namespace DigraphCMS\Content;

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

// Always add the default system routes directory
Router::addSource(__DIR__ . '/../../routes');

class Router
{
    protected static $sources = [];

    /**
     * Get a list of all the actions available for the given page
     *
     * @param Page $page
     * @return URL[]
     */
    public static function pageActions(Page $page): array
    {
        $urls = [];
        foreach ($page->routeClasses() as $c) {
            foreach (self::search("@$c/*.action.php") as $file) {
                $action = basename($file);
                $action = substr($action, 0, strlen($action) - 11);
                if ($action == '@wildcard' || $action == 'index') {
                    continue;
                }
                if (!preg_match('/\.[a-z0-9]+$/', $action)) {
                    $urls[] = $page->url($action);
                }
            }
        }
        return $urls;
    }

    /**
     * Get a list of all the static actions available for the given route
     *
     * @param string $route
     * @return URL[]
     */
    public static function staticActions(string $route): array
    {
        $urls = [];
        foreach (self::search("~$route/*.action.php") as $file) {
            $action = basename($file);
            $action = substr($action, 0, strlen($action) - 11);
            if ($action == '@wildcard' || $action == 'index') {
                continue;
            }
            if (!preg_match('/\.[a-z0-9]+$/', $action)) {
                $urls[] = new URL("/~$route/$action.html");
            }
        }
        return $urls;
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
        $route = Context::url()->route();
        if (!Context::page()) {
            $route = preg_replace('@^([^~])@', '~$1', $route);
        }
        foreach (static::$sources as $source) {
            foreach (glob("$source/$route/$glob") as $file) {
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
    public static function pageRoute(Page $page, string $action)
    {
        Context::page($page);
        // try specific routes first
        foreach ($page->routeClasses() as $c) {
            $output = self::tryRoute("@$c/$action");
            if ($output !== null) {
                Context::end();
                return $output;
            }
        }
        // try wildcard routes last
        foreach ($page->routeClasses() as $c) {
            $output = self::tryRoute("@$c/@wildcard");
            if ($output !== null) {
                Context::end();
                return $output;
            }
        }
        return null;
    }

    /**
     * Search for and execute a static route for a given route and action. 
     */
    public static function staticRoute(string $route, string $action)
    {
        $route = preg_replace('/^~/', '', $route);
        // try specific route
        $output = self::tryRoute("~$route/$action");
        if ($output !== null) {
            return $output;
        }
        // try wildcard route
        $output = self::tryRoute("~$route/@wildcard");
        if ($output !== null) {
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
            $path = "$source/~$route/$action.action.php";
            if (is_file($path)) {
                return true;
            }
        }
        return false;
    }

    protected static function tryRoute(string $route)
    {
        foreach (self::$sources as $source) {
            $path = "$source/$route.action.php";
            if (is_file($path)) {
                return require_file($path);
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
