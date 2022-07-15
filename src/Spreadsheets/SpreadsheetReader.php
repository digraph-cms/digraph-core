<?php

namespace DigraphCMS\Spreadsheets;

use Generator;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Memory-efficient and simple to use way of iterating through the contents of
 * a spreadsheet, with each row converted into an associative array using the
 * values of the first row as keys.
 */
class SpreadsheetReader
{
    public static function rows(string $file, string $type = null): Generator
    {
        // try to expand available memory because PhpSpreadsheet is thirsty
        ini_set('memory_limit', '2048M');
        // set up reader
        if ($type) $reader = IOFactory::createReader(ucfirst($type));
        else $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($file)->getActiveSheet();
        // build headers array
        $headers = [];
        foreach ($sheet->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
            $headers[] = strtolower($cell->getValue());
        }
        // get iterator for the rest of the rows and begin yielding non-empty rows
        $iterator = $sheet->getRowIterator(2, $sheet->getHighestDataRow());
        foreach ($iterator as $row) {
            $rowData = [];
            $hasData = false;
            foreach ($row->getCellIterator() as $cell) {
                $cell = $cell->getValue();
                $hasData = $hasData || $cell !== null;
                $rowData[] = $cell;
            }
            if ($hasData) yield array_combine($headers, $rowData);
        }
    }
}
