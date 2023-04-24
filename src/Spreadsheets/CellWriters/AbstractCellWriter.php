<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractCellWriter
{
    protected $value;
    protected $fill;

    abstract public function transformCell(Cell $style);

    public function __construct($value)
    {
        if (is_object($value)) $value = clone $value;
        $this->value = $value;
    }

    public function write(Worksheet $sheet, int $column, int $row)
    {
        $this->transformCell($sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row));
    }

    public function fill(): ?string
    {
        return $this->fill;
    }

    public function setFill(?string $fill)
    {
        if (!$fill) $this->fill = null;
        else {
            $fill = strtoupper($fill);
            if (!preg_match('/[0-9A-F]{8}/', $fill)) throw new \Exception("Fill must be 8 hex digits i.e. FFCCCCCC");
            $this->fill = $fill;
        }
        return $this;
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