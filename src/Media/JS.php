<?php

namespace DigraphCMS\Media;

use DigraphCMS\Config;
use JShrink\Minifier;

class JS
{
    public static function js(string $js, string $path = '/_source.js'): string
    {
        // minify if configured
        if (Config::get('files.js.minify')) {
            $js = static::minify($js);
        }
        // return;
        return $js;
    }

    public static function minify(string $js): string
    {
        return Minifier::minify($js);
    }
}
