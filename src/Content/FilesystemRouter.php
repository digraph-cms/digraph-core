<?php

namespace DigraphCMS\Content;

use DigraphCMS\Context;

// Always add the default system routes directory
FilesystemRouter::addSource(__DIR__ . '/../../routes');

class FilesystemRouter
{
    protected static $sources = [];

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
     * Search for and execute a route handler for a given request, response, and
     * page. Returns a string if handler is executed, null otherwise.
     */
    public static function pageRoute(Page $page, string $action): ?string
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
     * Search for and execute a static route for a given request and response. 
     * Returns a string if handler is executed, null otherwise.
     */
    public static function staticRoute(string $route, string $action): ?string
    {
        $routes = [
            $route,
            '_any'
        ];
        foreach ($routes as $r) {
            $output = self::tryRoute("~$r/$action");
            if ($output !== null) {
                return $output;
            }
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

    protected static function tryRoute(string $route): ?string
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

function require_file(string $file): string
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
