<?php

namespace DigraphCMS\CodeMirror;

use DigraphCMS\Config;
use DigraphCMS\UI\Theme;

// automatically load theme assets
Theme::addBlockingPageJs('/forms/codemirror/codemirror.min.js');
Theme::addBlockingPageJs('/forms/codemirror/addon/comment/comment.js');
Theme::addInternalPageCss('/forms/codemirror/codemirror.css');
Theme::addPageJs('/forms/codemirror/integration.js');
Theme::addInternalPageCss('/forms/codemirror/theme/*.css');

class CodeMirror
{
    protected static $loadedScripts = [];

    public static function loadMode(string $mode)
    {
        static::load("mode/$mode/$mode");
    }

    public static function load(string $script)
    {
        if (in_array($script, static::$loadedScripts)) {
            return;
        }
        // load dependencies
        foreach (Config::get("codemirror.dependencies.$script") ?? [] as $dep) {
            static::load($dep);
        }
        // load script
        static::$loadedScripts[] = $script;
        Theme::addBlockingPageJs("/forms/codemirror/$script.js");
    }
}
