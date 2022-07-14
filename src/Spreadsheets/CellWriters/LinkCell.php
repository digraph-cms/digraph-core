<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use PhpOffice\PhpSpreadsheet\Cell\Cell;

class LinkCell extends AbstractCellWriter
{
    protected $url;
    public function __construct($value, $url)
    {
        parent::__construct($value);
        $this->url = $url;
    }

    public function transformCell(Cell $cell)
    {
        $cell->setValue($this->value);
        static::hyperlink($cell, $this->url);
    }
}
