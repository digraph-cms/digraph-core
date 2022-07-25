<?php

namespace DigraphCMS\UI\Pagination;

class ColumnSortingHeader extends ColumnHeader
{
    protected $table;
    protected $column, $select;

    /**
     * @param string $label
     * @param string $column
     * @param \DigraphCMS\DB\AbstractMappedSelect|\Envms\FluentPDO\Queries\Select|null $select
     */
    public function __construct(string $label, string $column, $select = null)
    {
        $this->column = $column;
        $this->select = $select;
        parent::__construct(
            $label,
            [$this, 'callback']
        );
    }

    /**
     * @param PaginatedTable $table
     * @return $this
     */
    public function setTable(PaginatedTable $table)
    {
        $this->table = $table;
        return $this;
    }

    protected function callback(bool $asc)
    {
        $this->select()->order(null);
        $this->select()->order($this->column . ' ' . ($asc ? 'asc' : 'desc'));
    }

    protected function select()
    {
        return $this->select
            ?? $this->table->source();
    }
}
