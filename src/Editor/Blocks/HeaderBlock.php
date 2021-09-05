<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\UI\Theme;

class HeaderBlock extends AbstractBlock
{
    public static function load()
    {
        Theme::addBlockingPageJs('/editor/blocks/header.js');
    }

    public static function jsClass(): ?string
    {
        return '{ class: Header, config: {defaultLevel: 1}, shortcut: \'CMD+H\' }';
    }

    public function render(): string
    {
        $level = $this->data()['level'];
        $text = $this->data()['text'];
        $id = $this->id();
        return "<h$level class='referenceable-block' id='$id'>$text</h$level>";
    }
}
