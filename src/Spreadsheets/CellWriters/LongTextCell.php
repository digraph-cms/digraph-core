<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

class LongTextCell extends AbstractCellWriter
{

    public function cell(): \OpenSpout\Common\Entity\Cell
    {
        $cell = parent::cell();
        $style = $cell->style
            ->withShouldWrapText(true);
        return $cell->withStyle($style);
    }

}
