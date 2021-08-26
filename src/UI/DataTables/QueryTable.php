<?php

namespace DigraphCMS\UI\DataTables;

class QueryTable extends AbstractPaginatedTable
{
    protected $query, $callback, $headers;

    /**
     * Must be given a mapped query object and callable item for handling
     *
     * @param \DigraphCMS\DB\AbstractMappedSelect|\Envms\FluentPDO\Queries\Select $query
     * @param callable $callback
     */
    public function __construct($query, callable $callback, array $headers = [])
    {
        parent::__construct($query->count());
        $this->query = $query;
        $this->callback = $callback;
        $this->headers = $headers;
    }

    public function body(): array
    {
        static $body;
        if ($body === null) {
            $body = [];
            $this->query->offset($this->paginator->startItem());
            $this->query->limit($this->paginator->perPage());
            while ($item = $this->query->fetch()) {
                $body[] = ($this->callback)($item, $this);
            }
        }
        return $body;
    }
}
