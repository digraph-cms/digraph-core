<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use DateTime;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * @method __construct(DateTime $date)
 * @property DateTime $value
 */
class DateCell extends AbstractCellWriter
{
    public function transformCell(Cell $cell)
    {
        $cell->setValue(Date::PHPToExcel($this->value));
        $cell->getStyle()
            ->getNumberFormat()
            ->setFormatCode('mmm d, yyyy');
    }
}