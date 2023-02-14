<?php

namespace DigraphCMS\RichContent;

class Markdown
{
    public static function parse(string $input): string
    {
        // TODO: Look into replacing/updating parsedown to avoid deprecations
        return @static::parsedown()->text($input);
    }

    protected static function parsedown(): ParsedownDigraph
    {
        static $parsedown;
        if (!$parsedown) {
            $parsedown = new ParsedownDigraph();
        }
        return $parsedown;
    }
}
