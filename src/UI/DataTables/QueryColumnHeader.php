<?php

namespace DigraphCMS\UI\DataTables;

use DigraphCMS\Context;
use DigraphCMS\DB\AbstractMappedSelect;

class QueryColumnHeader extends ColumnHeader
{
    public function __construct(string $label, string $column, AbstractMappedSelect $select)
    {
        parent::__construct(
            $label,
            function (bool $asc) use ($column, $select) {
                $select->order(null);
                $select->order($column . ' ' . ($asc ? 'asc' : 'desc'));
            }
        );
    }
}
