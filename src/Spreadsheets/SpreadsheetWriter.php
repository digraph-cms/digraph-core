<?php

namespace DigraphCMS\Spreadsheets;

use DigraphCMS\Config;
use DigraphCMS\FS;
use DigraphCMS\Spreadsheets\CellWriters\AbstractCellWriter;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\WriterInterface;

class SpreadsheetWriter
{

    protected WriterInterface $writer;

    protected string $temp_file;

    protected $headers = false;

    protected $freezeColumns = 0;

    public function __construct(string $extension = 'xlsx')
    {
        $tempdir = Config::cachePath() . '/openspout';
        FS::mkdir($tempdir);
        $this->writer = match ($extension) {
            'xlsx'  => new \OpenSpout\Writer\XLSX\Writer(
                new \OpenSpout\Writer\XLSX\Options(tempFolder: $tempdir),
            ),
            'ods'   => new \OpenSpout\Writer\ODS\Writer(
                new \OpenSpout\Writer\ODS\Options(tempFolder: $tempdir),
            ),
            'csv'   => new \OpenSpout\Writer\CSV\Writer(),
            default => throw new InvalidArgumentException("Invalid spreadsheet format, only .xlsx, .ods, and .csv files are supported")
        };
        $this->temp_file = Config::cachePath() . '/spreadsheet_writing/' . uniqid() . '.' . $extension;
        FS::touch($this->temp_file);
        $this->writer->openToFile($this->temp_file);
    }

    public function save(string $file_path): void
    {
        // close
        $this->writer->close();
        // move temp file to final location
        FS::copy($this->temp_file, $file_path);
        FS::delete($this->temp_file);
    }

    /**
     * @param array<mixed> $cells
     */
    public function writeHeaders(array $cells)
    {
        $this->headers = true;
        $cells = $this->prepareCells($cells);
        foreach ($cells as $cell) {
            $cell->getStyle()->setFontBold();
            $cell->getStyle()->setBackgroundColor('FFCCCCCC');
            $cell->getStyle()->setFontColor('FF000000');
        }
        $this->writer->addRow(new Row($cells));
    }

    /**
     * @param array<mixed> $cells
     * @return array<Cell>
     */
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
