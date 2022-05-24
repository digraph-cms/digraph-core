<?php

namespace DigraphCMS\UI\DataTables\CellWriters;

use DateTime;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * @method __construct(DateTime $date)
 * @property DateTime $value
 */
class DateTimeCell extends AbstractCellWriter
{
    public function transformCell(Cell $cell)
    {
        $cell->setValue($this->value);
        $cell->getStyle()
            ->getNumberFormat()
            ->setFormatCode(
                NumberFormat::FORMAT_DATE_DATETIME
            );
    }
}
