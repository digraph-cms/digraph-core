<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\UI\Theme;

class ListBlock extends AbstractBlock
{
    public static function load()
    {
        Theme::addBlockingPageJs('/editor/blocks/nested-list.js');
    }

    protected static function jsClass(): string
    {
        return 'NestedList';
        // return '{ class: NestedList, inlineToolbar: true, shortcut: \'CMD+L\' }';
    }

    protected static function shortcut(): ?string
    {
        return 'CMD+L';
    }

    public function render(): string
    {
        $id = $this->id();
        $tag = $this->data()['style'] == 'ordered' ? 'ol' : 'ul';
        $out = "<$tag class='referenceable-block' id='$id'>" . PHP_EOL;
        $out .= $this->renderItems($this->data()['items']);
        $out .= $this->anchorLink();
        $out .= "</$tag>" . PHP_EOL;
        return $out;
    }

    protected function renderItems(array $items): string
    {
        $out = '';
        foreach ($items as $item) {
            $content = $item['content'];
            $out .= "<li>$content";
            if ($item['items']) {
                $tag = $this->data()['style'] == 'ordered' ? 'ol' : 'ul';
                $out .= "<$tag>" . PHP_EOL;
                $out .= $this->renderItems($item['items']);
                $out .= "</$tag>" . PHP_EOL;
            }
            $out .= "</li>" . PHP_EOL;
        }
        return $out;
    }
}
