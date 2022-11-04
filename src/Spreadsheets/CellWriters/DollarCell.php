<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * @method __construct(float $amount)
 * @property float $value
 */
class DollarCell extends AbstractCellWriter
{
    public function transformCell(Cell $cell)
    {
        $cell->setValue($this->value);
        $cell->getStyle()
            ->getNumberFormat()
            ->setFormatCode(
                NumberFormat::FORMAT_CURRENCY_USD_SIMPLE
            );
    }
}
