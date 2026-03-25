<?php

namespace DigraphCMS\Spreadsheets;

use DigraphCMS\Config;
use DigraphCMS\FS;
use DigraphCMS\Spreadsheets\CellWriters\AbstractCellWriter;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Entity\SheetView;

class SpreadsheetWriter
{

    protected WriterInterface $writer;

    protected string $temp_file;

    protected $headers = false;

    protected $freezeColumns = 0;

    public function __construct(string $extension = 'xlsx')
    {
        $this->writer = match ($extension) {
            'xlsx'  => new \OpenSpout\Writer\XLSX\Writer(),
            'ods'   => new \OpenSpout\Writer\ODS\Writer(),
            'csv'   => new \OpenSpout\Writer\CSV\Writer(),
            default => throw new InvalidArgumentException("Invalid spreadsheet format, only .xlsx, .ods, and .csv files are supported")
        };
        $this->temp_file = Config::cachePath() . '/spreadsheet_writing/' . uniqid() . '.' . $extension;
        $this->writer->openToFile($this->temp_file);
    }

    public function save(string $file_path): void
    {
        // if there are headers, try to freeze top row
        if ($this->headers) {
            if ($this->writer instanceof \OpenSpout\Writer\XLSX\Writer) {
                $this->writer->getCurrentSheet()->getSheetView()->setFreezeRow(2);
            }
        }
        // close
        $this->writer->close();
        // move temp file to final location
        FS::copy($this->temp_file, $file_path);
        FS::delete($this->temp_file);
    }

    public function writeHeaders(array $cells)
    {
        $this->headers = true;
        $cells = $this->prepareCells($cells);
        foreach ($cells as $cell) {
            $cell->style->fontBold = true;
            $cell->style->backgroundColor = '#CCCCCC';
            $cell->style->fontColor = '#000000';
        }
        $this->writer->addRow(new Row($cells));
    }

    protected function prepareCells(array $cells): array
    {
        return array_map(
            $this->prepareCell(...),
            $cells,
        );
    }

    protected function prepareCell(mixed $cell): Cell
    {
        if ($cell instanceof Cell)
            return $cell;
        elseif ($cell instanceof AbstractCellWriter)
            return $cell->cell();
        else
            return Cell::fromValue($cell);
    }

    public function writeRow(array $cells)
    {
        $this->writer->addRow(
            new Row($this->prepareCells($cells)),
        );
    }

}
