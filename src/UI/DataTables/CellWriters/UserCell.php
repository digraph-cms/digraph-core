<?php

namespace DigraphCMS\UI\DataTables\CellWriters;

use DigraphCMS\Users\User;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * @method __construct(User)
 * @property User $value
 */
class UserCell extends AbstractCellWriter
{
    public function transformCell(Cell $cell)
    {
        $cell->setValue($this->value->name());
        static::hyperlink($cell, $this->value->profile());
    }
}
