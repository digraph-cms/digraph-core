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
        $cell->getStyle()->setFormat('m/d/yy');
        return $cell;
    }

}
