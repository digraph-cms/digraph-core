<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use DateTimeInterface;

/**
 * @method __construct(DateTimeInterface $date)
 * @property DateTimeInterface $value
 */
class DateTimeCell extends AbstractCellWriter
{

    public function cell(): \OpenSpout\Common\Entity\Cell
    {
        $cell = parent::cell();
        $cell->getStyle()->setFormat('m/d/yy h:mm');
        return $cell;
    }

}
