<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

class LongTextCell extends AbstractCellWriter
{

    public function cell(): \OpenSpout\Common\Entity\Cell
    {
        $cell = parent::cell();
        $cell->getStyle()->setShouldWrapText(true);
        return $cell;
    }

}
