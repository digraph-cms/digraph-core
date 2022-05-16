<?php

namespace DigraphCMS\UI\DataTables;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DownloadWriter
{
    protected $spreadsheet;
    protected $freezeColumns = 0;

    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
    }

    public function freezeColumns(): int {
        return $this->freezeColumns;
    }

    /**
     * Set how many columns should be frozen
     *
     * @param integer $columns
     * @return $this
     */
    public function setFreezeColumns(int $columns) {
        $this->freezeColumns = $columns;
        return $this;
    }

    public function writeHeaders(array $cells)
    {
        $this->spreadsheet->setActiveSheetIndex(0);
        $row = 1;
        foreach (array_values($cells) as $i => $cell) {
            $this->spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(
                $i + 1,
                $row,
                $cell
            );
            $cell = $this->spreadsheet->getActiveSheet()->getCellByColumnAndRow($i+1,$row);
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $cell->getStyle()->getFill()->setStartColor(new Color('FFCCCCCC'));
        }
    }

    public function writeRow(array $cells)
    {
        $this->spreadsheet->setActiveSheetIndex(0);
        $row = $this->spreadsheet->getActiveSheet()->getHighestDataRow()+1;
        foreach (array_values($cells) as $i => $cell) {
            $this->spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(
                $i + 1,
                $row,
                $cell
            );
        }
    }
}
