<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\UI\Theme;

class ParagraphBlock extends AbstractBlock
{
    public static function load()
    {
        Theme::addBlockingPageJs('/editor/blocks/header.js');
    }

    public static function jsClass(): string
    {
        return 'Paragraph';
    }

    public static function jsConfigString(): ?string
    {
        return null;
    }

    public function doRender(): string
    {
        return "<p>" . PHP_EOL .
            $this->data()['text'] . PHP_EOL .
            $this->anchorLink() . PHP_EOL .
            "<p>";
    }
}
