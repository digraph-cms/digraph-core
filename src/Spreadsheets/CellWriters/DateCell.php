<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use DateTimeInterface;

/**
 * @method __construct(DateTimeInterface $date)
 * @property DateTimeInterface $value
 */
class DateCell extends AbstractCellWriter
{

    public function cell(): \OpenSpout\Common\Entity\Cell
    {
        $cell = parent::cell();
        $style = $cell->style
            ->withFormat('m/d/yy');
        return $cell->withStyle($style);
    }

}
