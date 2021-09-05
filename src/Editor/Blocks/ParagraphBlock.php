<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\UI\Theme;

class ParagraphBlock extends AbstractBlock
{
    public static function load()
    {
        Theme::addBlockingPageJs('/editor/blocks/header.js');
    }

    public static function jsClass(): ?string
    {
        return null;
    }

    public function render(): string
    {
        $text = $this->data()['text'];
        $id = $this->id();
        return "<p class='referenceable-block' id='$id'>$text</p>";
    }
}
