<?php

namespace DigraphCMS\Editor;

use DigraphCMS\Context;
use DigraphCMS\UI\Theme;

class Editor
{
    protected static $contextUUID;

    public static function contextUUID(string $contextUUID = null): ?string
    {
        if ($contextUUID !== null) {
            static::$contextUUID = $contextUUID;
        }
        return static::$contextUUID;
    }

    public static function load()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;
        // mark as private because there are csrf tokens
        Context::response()->private(true);
        // load all the types
        foreach (Blocks::types() as $name => $class) {
            $class::load();
        }
        // set up editor config script
        $script = 'Digraph.editorTools = {' . PHP_EOL;
        foreach (Blocks::types() as $name => $class) {
            if ($class = $class::jsConfigString()) {
                $script .= "$name: $class," . PHP_EOL;
            }
        }
        $script .= "};" . PHP_EOL;
        Theme::addInlinePageJs($script);
        Theme::addInternalPageCss('/editor/editor.css');
        Theme::addBlockingPageJs('/editor/editor.js');
        Theme::addBlockingPageJs('/editor/digraph-editor.js');
    }
}
