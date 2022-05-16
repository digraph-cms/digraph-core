<?php

namespace DigraphCMS\UI\DataTables;

use PhpOffice\PhpSpreadsheet\Spreadsheet;


class DownloadWriter
{
    protected $spreadsheet;

    public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
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
