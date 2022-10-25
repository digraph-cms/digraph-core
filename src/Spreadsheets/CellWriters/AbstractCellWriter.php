<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractCellWriter
{
    protected $value;

    abstract public function transformCell(Cell $style);

    public function __construct($value)
    {
        if (is_object($value)) $value = clone $value;
        $this->value = $value;
    }

    public function write(Worksheet $sheet, int $column, int $row)
    {
        $this->transformCell($sheet->getCellByColumnAndRow($column, $row));
    }

    public function fill(): ?string
    {
        return null;
    }

    protected static function hyperlink(Cell $cell, $url, $style = true)
    {
        $cell->getHyperlink()->setUrl($url);
        if ($style) {
            $cell->getStyle()->getFont()
                ->setUnderline(true)
                ->setColor(new Color('FF000099'));
        }
    }
}
