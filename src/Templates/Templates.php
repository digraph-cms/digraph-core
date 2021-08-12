<?php

namespace DigraphCMS\Templates;

use DigraphCMS\Context;
use DigraphCMS\HTTP\Response;

// Always add the default system templates directory
Templates::addSource(__DIR__ . '/../../templates');

class Templates
{
    protected static $sources = [];

    /**
     * Add a source directory to the top of the list of directories to search in
     * for template files. 
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

    public static function wrapResponse(Response $response)
    {
        Context::response($response);
        $template = $response->template();
        $extension = strtolower(pathinfo($template, PATHINFO_EXTENSION));
        $file = static::locateFile($template);
        if (!$file) {
            throw new \Exception("Couldn't locate template file for template $template");
        }
        if ($extension == 'php') {
            $response->content(require_file($file));
        }
    }

    protected static function locateFile(string $template): ?string
    {
        if (strpos('..', $template) !== false) {
            return null;
        }
        foreach (static::$sources as $dir) {
            $file = "$dir/$template";
            if (is_file($file)) {
                return $file;
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
