<?php

namespace DigraphCMS\UI\DataLists;

class QueryList extends AbstractPaginatedList
{
    protected $query, $callback, $class;

    /**
     * Must be given a mapped query object and callable item for handling
     *
     * @param \DigraphCMS\DB\AbstractMappedSelect|\Envms\FluentPDO\Queries\Select $query
     * @param callable $callback
     */
    public function __construct($query, callable $callback, string $class = null)
    {
        parent::__construct($query->count());
        $this->query = $query;
        $this->callback = $callback;
        $this->class = $class;
    }

    public function class(): string
    {
        return trim(parent::class() . ' ' . $this->class);
    }

    public function items(): array
    {
        static $items;
        if ($items === null) {
            $items = [];
            $this->query->offset($this->paginator->startItem());
            $this->query->limit($this->paginator->perPage());
            while ($item = $this->query->fetch()) {
                $items[] = ($this->callback)($item, $this);
            }
        }
        return $items;
    }
}