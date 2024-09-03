<?php

namespace DigraphCMS\Spreadsheets;

use DigraphCMS\Spreadsheets\CellWriters\AbstractCellWriter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SpreadsheetWriter
{
    protected $spreadsheet;
    protected $headers = false;
    protected $freezeColumns = 0;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet;
    }

    public function spreadsheet(): Spreadsheet
    {
        // set autosized widths by default
        foreach ($this->spreadsheet->getActiveSheet()->getColumnIterator() as $column) {
            $dimension = $this->spreadsheet->getActiveSheet()->getColumnDimension($column->getColumnIndex());
            $dimension->setAutoSize(true);
        }
        // wrap up formatting stuff
        $this->spreadsheet->setActiveSheetIndex(0);
        $this->spreadsheet->getActiveSheet()->setAutoFilter(
            $this->spreadsheet->getActiveSheet()->calculateWorksheetDimension()
        );
        // freeze rows/columns
        if ($this->headers) $this->spreadsheet->getActiveSheet()->freezePane(Coordinate::stringFromColumnIndex($this->freezeColumns() + 1) . '2');
        else $this->spreadsheet->getActiveSheet()->freezePane(Coordinate::stringFromColumnIndex($this->freezeColumns() + 1) . '1');
        // return
        return $this->spreadsheet;
    }

    public function freezeColumns(): int
    {
        return $this->freezeColumns;
    }

    /**
     * Set how many columns should be frozen
     *
     * @param integer $columns
     * @return static
     */
    public function setFreezeColumns(int $columns)
    {
        $this->freezeColumns = $columns;
        return $this;
    }

    public function writeHeaders(array $cells)
    {
        $this->headers = true;
        $this->spreadsheet->setActiveSheetIndex(0);
        $row = 1;
        foreach (array_values($cells) as $i => $cell) {
            $this->spreadsheet->getActiveSheet()
                ->setCellValue(
                    Coordinate::stringFromColumnIndex($i + 1) . $row,
                    $cell
                );
            $cell = $this->spreadsheet->getActiveSheet()
                ->getCell(Coordinate::stringFromColumnIndex($i + 1) . $row);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $cell->getStyle()->getFill()->setStartColor(new Color('FFCCCCCC'));
        }
    }

    public function writeRow(array $cells)
    {
        $this->spreadsheet->setActiveSheetIndex(0);
        $row = $this->spreadsheet->getActiveSheet()->getHighestDataRow() + 1;
        foreach (array_values($cells) as $i => $cell) {
            // set value
            if ($cell instanceof AbstractCellWriter) {
                $cell->write($this->spreadsheet->getActiveSheet(), $i + 1, $row);
            } else {
                $this->spreadsheet->getActiveSheet()->setCellValue(
                    Coordinate::stringFromColumnIndex($i + 1) . $row,
                    $cell
                );
            }
            // set fill
            $style = $this->spreadsheet->getActiveSheet()
                ->getCell(Coordinate::stringFromColumnIndex($i + 1) . $row)
                ->getStyle();
            $style->getFill()->setFillType(Fill::FILL_SOLID);
            if ($cell instanceof AbstractCellWriter && $fill = $cell->fill()) {
                $style->getFill()->setStartColor(new Color($fill));
            } else {
                $style->getFill()->setStartColor(new Color($row % 2 ? 'FFEEEEEE' : 'FFFFFFFF'));
            }
        }
    }
}
