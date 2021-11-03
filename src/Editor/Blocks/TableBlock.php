<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\UI\Theme;

class TableBlock extends AbstractBlock
{
    public static function load()
    {
        Theme::addBlockingPageJs('/editor/blocks/table.js');
    }

    protected static function jsClass(): string
    {
        return 'Table';
    }

    protected static function shortcut(): ?string
    {
        return 'CMD+T';
    }

    public function doRender(): string
    {
        $out = "<table class='referenceable-block'>" . PHP_EOL;
        foreach ($this->data()['content'] as $i => $row) {
            $ct = $i == 0 && $this->data()['withHeadings'] ? 'th' : 'td';
            $out .= '<tr>' . PHP_EOL;
            foreach ($row as $cell) {
                $out .= "<$ct>$cell</$ct>" . PHP_EOL;
            }
            $out .= '</tr>' . PHP_EOL;
        }
        $out .= "</table>" . PHP_EOL;
        return $out;
    }
}
