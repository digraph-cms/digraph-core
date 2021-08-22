<?php

namespace DigraphCMS\UI;

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
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

    protected static function render(string $template, array $fields = []): string
    {
        Context::clone();
        $template = Config::get("templates.aliases.$template") ?? $template;
        $output = static::doRender($template, $fields);
        Context::end();
        return $output;
    }

    protected static function doRender(string $template, array $fields = []): string
    {
        // make sure file exists
        $file = static::locateFile($template);
        if (!$file) {
            throw new \Exception("Couldn't locate template file for template $template");
        }
        $extension = strtolower(pathinfo($template, PATHINFO_EXTENSION));
        // add fields to context
        Context::fields()->merge($fields);
        // built-in handlers
        switch ($extension) {
            case 'php':
                return require_file($file);
        }
        // try dispatching event in case something else wants to handle this extension
        if ($return = Dispatcher::firstValue("onTemplateApply_$extension", [$file])) {
            return $return;
        }
        // throw exception if we haven't returned anything yet
        throw new \Exception("Nothing could handle a .$extension template");
    }

    public static function wrapResponse(Response $response)
    {
        Context::clone();
        Context::response($response);
        $fields = Context::fields();
        if (!$fields['page.name']) {
            // try to infer page name from response content
            $content = $response->content();
            if (preg_match("@<h1[^>]*>(.+)</h1>@i", $content, $matches)) {
                $fields['page.name'] = strip_tags($matches[1]);
            } else {
                $fields['page.name'] = strip_tags(Context::url()->name());
            }
        }
        $response->content(static::render($response->template()));
        Context::end();
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
