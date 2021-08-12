<?php

namespace DigraphCMS\Content;

use DigraphCMS\Context;

// Always add the default system routes directory
Router::addSource(__DIR__ . '/../../routes');

class Router
{
    protected static $sources = [];
    const ROUTE_DONE = null;
    const ROUTE_CONTINUE = 1;
    const ROUTE_ABORT = 2;

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
        Context::clone();
        Context::page($page);
        $classes = $page->routeClasses();
        foreach ($classes as $c) {
            $output = self::tryRoute("@$c/$action");
            if ($output !== null) {
                Context::end();
                return $output;
            }
        }
        Context::end();
        return null;
    }

    /**
     * Search for and execute a static route for a given route and action. 
     */
    public static function staticRoute(string $route, string $action)
    {
        $output = self::tryRoute("~$route/$action");
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
        $routes = [
            $route,
            '_any'
        ];
        foreach ($routes as $r) {
            foreach (self::$sources as $source) {
                $path = "$source/~$r/$action.action.php";
                if (is_file($path)) {
                    return true;
                }
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
        $return = require $file;
        if ($return !== 1) {
            ob_end_clean();
            return $return;
        }
    } catch (\Throwable $th) {
        ob_end_clean();
        throw $th;
    }
    return ob_get_clean();
}
