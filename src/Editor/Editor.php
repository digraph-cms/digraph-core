<?php

namespace DigraphCMS\Editor;

use DigraphCMS\Context;
use DigraphCMS\UI\Theme;

class Editor
{
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
        $script = 'Digraph.editorTools = {};' . PHP_EOL;
        foreach (Blocks::types() as $name => $class) {
            if ($class = $class::jsClass()) {
                $script .= "Digraph.editorTools.$name = $class;" . PHP_EOL;
            }
        }
        Theme::addInlinePageJs($script);
        Theme::addInternalPageCss('/editor/editor.css');
        Theme::addBlockingPageJs('/editor/editor.js');
        Theme::addBlockingPageJs('/editor/digraph-editor.js');
    }
}
