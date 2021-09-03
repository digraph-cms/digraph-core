<?php

namespace DigraphCMS\UI\DataTables;

class QueryColumnHeader extends ColumnHeader
{
    /**
     * Undocumented function
     *
     * @param string $label
     * @param string $column
     * @param \DigraphCMS\DB\AbstractMappedSelect|\Envms\FluentPDO\Queries\Select $select
     */
    public function __construct(string $label, string $column, $select, bool $header = false)
    {
        parent::__construct(
            $label,
            function (bool $asc) use ($column, $select) {
                $select->order(null);
                $select->order($column . ' ' . ($asc ? 'asc' : 'desc'));
            },
            $header
        );
    }
}
