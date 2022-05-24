<?php

namespace DigraphCMS\UI\DataTables\CellWriters;

use DigraphCMS\Content\Page;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * @method __construct(Page)
 * @property Page $value
 */
class PageCell extends AbstractCellWriter
{
    public function transformCell(Cell $cell)
    {
        $cell->setValue($this->value->name());
        static::hyperlink($cell, $this->value->url());
    }
}
