<?php

namespace DigraphCMS\CodeMirror;

use DigraphCMS\Config;
use DigraphCMS\Media\Media;
use DigraphCMS\UI\Theme;

// automatically load theme assets
Theme::addBlockingPageJs('/node_modules/codemirror/lib/codemirror.js');
Theme::addBlockingPageJs('/node_modules/codemirror/addon/comment/comment.js');
Theme::addInternalPageCss('/node_modules/codemirror/lib/codemirror.css');
Theme::addBlockingPageJs('/forms/codemirror/integration/*.js');
Theme::addInternalPageCss('/forms/codemirror/theme/*.css');

class CodeMirror
{
    protected static $loadedScripts = [];

    public static function loadMode(string $mode)
    {
        // load global addons
        foreach (Config::get("codemirror.dependencies.all") ?? [] as $dep) {
            static::load($dep);
        }
        // load mode
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
        if (Media::locate("/forms/codemirror/$script.js")) {
            Theme::addBlockingPageJs("/forms/codemirror/$script.js");
        } else {
            Theme::addBlockingPageJs("/node_modules/codemirror/$script.js");
        }
    }
}
