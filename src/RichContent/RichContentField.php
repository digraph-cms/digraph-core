<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\UI\Theme;
use Formward\Fields\Container;
use Formward\Fields\DisplayOnly;
use Formward\Fields\Hidden;
use Formward\Fields\Textarea;

class RichContentField extends Container
{
    public static function load()
    {
        static $loaded = false;
        if (!$loaded) {
            RichContent::load();
            $loaded = true;
        }
    }

    public function default($content = null): RichContent
    {
        if ($content) {
            $this['value']->default($content->editorValue());
        }
        return new RichContent($this['value']->default());
    }

    public function value($content = null): RichContent
    {
        if ($content) {
            $this['value']->value($content->editorValue());
        }
        return new RichContent($this['value']->value());
    }

    public function required($set = null, $clientSide = true)
    {
        return $this['value']->required($set, false);
    }

    public function construct()
    {
        $this['value'] = new Textarea('');
    }

    public function __toString()
    {
        static::load();
        $this['value']->default($this->value()->editorValue());
        return parent::__toString();
    }
}
