<?php

namespace DigraphCMS\UI\DataTables;

use DigraphCMS\DB\AbstractMappedSelect;

class SelectTable extends AbstractPaginatedTable
{
    protected $select, $callback, $headers;

    /**
     * Must be given a mapped select object and callable item for handling
     *
     * @param AbstractMappedSelect $select
     * @param callable $callback
     */
    public function __construct(AbstractMappedSelect $select, callable $callback, array $headers = [])
    {
        parent::__construct($select->count());
        $this->select = $select;
        $this->callback = $callback;
        $this->headers = $headers;
    }

    public function body(): array
    {
        static $body;
        if ($body === null) {
            $body = [];
            $this->select->offset($this->paginator->startItem());
            $this->select->limit($this->paginator->perPage());
            while ($item = $this->select->fetch()) {
                $body[] = ($this->callback)($item, $this);
            }
        }
        return $body;
    }

    public function class(): string
    {
        return parent::class().' select-table';
    }
}
