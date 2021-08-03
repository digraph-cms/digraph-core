<?php

namespace DigraphCMS\Content;

use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\Response;

FilesystemRouter::addSource(__DIR__ . '/../../routes');

class FilesystemRouter
{
    protected static $sources = [];

    public static function addSource(string $dir)
    {
        if (($dir = realpath($dir)) && is_dir($dir) && !in_array($dir, self::$sources)) {
            array_unshift(self::$sources, $dir);
        }
    }

    public static function pageRoute(Request $request, Response $response, Page $page, string $action): ?string
    {
        $classes = $page->routeClasses();
        foreach ($classes as $c) {
            $output = self::tryRoute("@$c/$action", $request, $response, $page);
            if ($output !== null) {
                return $output;
            }
        }
        return null;
    }

    public static function staticRoute(Request $request, Response $response, string $route, string $action): ?string
    {
        $routes = [
            $route,
            '_any'
        ];
        foreach ($routes as $r) {
            $output = self::tryRoute("~$r/$action", $request, $response);
            if ($output !== null) {
                return $output;
            }
        }
        return null;
    }

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

    protected static function tryRoute(string $route, Request $request, Response $response, Page $page = null): ?string
    {
        foreach (self::$sources as $source) {
            $path = "$source/$route.action.php";
            if (is_file($path)) {
                return include_file($path, $request, $response, $page);
            }
        }
        return null;
    }
}

function include_file(string $file, Request $request, Response $response, Page $page = null): string
{
    Route::beginContext($request, $response, $page);
    ob_start();
    include $file;
    Route::endContext();
    return ob_get_clean();
}
