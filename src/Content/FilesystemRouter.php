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
        $classes = [
            '@' . $page->class(),
            '@_any'
        ];
        foreach ($classes as $class) {
            $output = self::tryRoute("$class/$action", $request, $response, $page);
            if ($output !== null) {
                return $output;
            }
        }
        return null;
    }

    public static function staticRoute(Request $request, Response $response, string $route, string $action): ?string
    {
        return null;
    }

    protected static function tryRoute(string $route, Request $request, Response $response, Page $page = null): ?string
    {
        foreach (self::$sources as $source) {
            $path = "$source/$route.action.php";
            if (is_file($path)) {
                return Route::include_file($path, $request, $response, $page);
            }
        }
        return null;
    }
}
