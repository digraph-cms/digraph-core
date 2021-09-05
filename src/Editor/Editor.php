<?php

namespace DigraphCMS\Editor;

use DigraphCMS\Media\DeferredFile;
use DigraphCMS\UI\Theme;

class Editor
{
    protected static $loaded;

    public static function load()
    {
        if (static::$loaded) {
            return;
        }
        static::$loaded = true;
        $script = new DeferredFile(
            'editor-config.js',
            function (DeferredFile $file) {
                $script = 'Digraph.editorTools = {};' . PHP_EOL;
                foreach (Blocks::types() as $name => $class) {
                    $class::load();
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
        Theme::addPageJs('/editor/digraph-editor.js');
    }
}
