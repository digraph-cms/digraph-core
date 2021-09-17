<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\UI\Theme;

class HeaderBlock extends AbstractBlock
{
    public static function load()
    {
        Theme::addBlockingPageJs('/editor/blocks/header.js');
    }

    protected static function jsClass(): string
    {
        return 'Header';
    }

    protected static function jsConfig(): array
    {
        return [
            'defaultLevel' => 1
        ];
    }

    protected static function shortcut(): ?string
    {
        return 'CMD+H';
    }

    public function render(): string
    {
        $level = $this->data()['level'];
        $text = $this->data()['text'];
        $id = $this->id();
        return "<h$level class='referenceable-block' id='$id'>$text" . PHP_EOL .
            $this->anchorLink() . PHP_EOL .
            "</h$level>";
    }
}