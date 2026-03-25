<?php

namespace DigraphCMS\Spreadsheets;

use Generator;
use OpenSpout\Common\Entity\Cell;
use RuntimeException;

/**
 * Reasonably memory-efficient and simple to use way of iterating through the contents of a spreadsheet, with each row converted into an associative array using the values of the first row as keys.
 */
class SpreadsheetReader
{

    /**
     * Get all rows beyond the first one, using the first one's values as array keys.
     */
    public static function rows(string $file, string|null $extension = null): Generator
    {
        // try to expand available memory because the uploaded file might be big
        ini_set('memory_limit', '2048M');
        // set up reader
        $extension = $extension
            ?? strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $reader = match ($extension) {
            'xlsx'  => new \OpenSpout\Reader\XLSX\Reader(),
            'ods'   => new \OpenSpout\Reader\ODS\Reader(),
            'csv'   => new \OpenSpout\Reader\CSV\Reader(),
            default => throw new RuntimeException("Invalid spreadsheet format, only .xlsx, .ods, and .csv files are supported")
        };
        // get iterator for first sheet
        $sheet = $reader->getSheetIterator()->current();
        $rows = $sheet->getRowIterator();
        // build headers array
        $headers = [];
        $header_row = $rows->current();
        if (!$header_row)
            return;
        foreach ($header_row->cells as $cell) {
            $headers[] = strtolower((string) $cell->getValue());
        }
        // get iterator for the rest of the rows and begin yielding non-empty rows
        while ($row = next($rows)) {
            $rowData = [];
            $hasData = false;
            foreach ($row->cells as $cell) {
                $cell = $cell->getValue();
                $hasData = $hasData || $cell !== null;
                $rowData[] = $cell;
            }
            if ($hasData)
                yield array_combine($headers, $rowData);
        }
    }

    /**
     * Get all rows of the spreadsheet as individual values, with numeric keys, without trying to use the first row as headers.
     */
    public static function rows_raw(string $file, string|null $extension = null): Generator
    {
        // try to expand available memory because the uploaded file might be big
        ini_set('memory_limit', '2048M');
        // set up reader
        $extension = $extension
            ?? strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $reader = match ($extension) {
            'xlsx'  => new \OpenSpout\Reader\XLSX\Reader(),
            'ods'   => new \OpenSpout\Reader\ODS\Reader(),
            'csv'   => new \OpenSpout\Reader\CSV\Reader(),
            default => throw new RuntimeException("Invalid spreadsheet format, only .xlsx, .ods, and .csv files are supported")
        };
        // get iterator for first sheet
        $sheet = $reader->getSheetIterator()->current();
        $rows = $sheet->getRowIterator();
        // get iterator for the rest of the rows and begin yielding
        while ($row = next($rows)) {
            yield array_map(
                fn(Cell $cell) => $cell->getValue(),
                $row->cells,
            );
        }
    }

}
