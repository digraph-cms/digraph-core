<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\UI\Theme;
use Formward\Fields\Container;
use Formward\Fields\DisplayOnly;
use Formward\Fields\Hidden;

class RichContentField extends Container
{
    public static function load()
    {
        static $loaded = false;
        if (!$loaded) {
            RichContent::load();
            Theme::addBlockingPageJs('/core/trix/trix.js');
            Theme::addBlockingPageJs('/core/trix/trix-integration.js');
            Theme::addInternalPageCss('/core/trix/trix.css');
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
        $this['value'] = new Hidden('');
        $this['trix'] = new DisplayOnly('');
        $this['attachments'] = new RichContentBlocksField("");
    }

    public function __toString()
    {
        static::load();
        $this['trix']->content(sprintf(
            '<trix-editor input="%s" class="trix-content" id="%s-editor"></trix-editor>',
            $this['value']->name(),
            $this->name()
        ));
        $this['attachments']->editorID($this->name());
        $this['value']->default($this->value()->editorValue());
        return parent::__toString();
    }
}
