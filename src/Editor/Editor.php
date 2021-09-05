<?php

namespace DigraphCMS\Editor;

use DigraphCMS\Media\DeferredFile;
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
        // load all the types
        foreach (Blocks::types() as $name => $class) {
            $class::load();
        }
        // set up editor config script
        $script = new DeferredFile(
            'editor-config.js',
            function (DeferredFile $file) {
                $script = 'Digraph.editorTools = {};' . PHP_EOL;
                foreach (Blocks::types() as $name => $class) {
                    if ($class = $class::jsClass()) {
                        $script .= "Digraph.editorTools.$name = $class;" . PHP_EOL;
                    }
                }
                file_put_contents($file->path(), $script);
            },
            'digraph-editor-config'
        );
        $script->write();
        Theme::addBlockingPageJs($script);
        Theme::addInternalPageCss('/editor/editor.css');
        Theme::addBlockingPageJs('/editor/editor.js');
        Theme::addBlockingPageJs('/editor/digraph-editor.js');
    }
}
