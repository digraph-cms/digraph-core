<?php

namespace DigraphCMS\UI\DataTables;

use DigraphCMS\Context;
use DigraphCMS\DataObjects\DSOQuery;
use DigraphCMS\DB\AbstractMappedSelect;
use Envms\FluentPDO\Queries\Select;

class QueryTable extends AbstractPaginatedTable
{
    protected $query, $callback, $headers, $body;

    /**
     * Must be given a mapped query object and callable item for handling
     *
     * @param DSOQuery|AbstractMappedSelect|Select $query
     * @param callable $callback
     */
    public function __construct($query, callable $callback, array $headers = [])
    {
        parent::__construct($query->count());
        $this->query = $query;
        $this->callback = $callback;
        $this->headers = $headers;
    }

    protected function writeDownloadFile(DownloadWriter $writer)
    {
        // load all query results into rows
        $query = clone $this->query;
        while ($r = $query->fetch()) {
            $writer->writeRow(call_user_func($this->downloadCallback, $r, $this));
        }
    }

    protected function downloadFileID(): string
    {
        return md5(serialize([
            $this->query->getQuery(),
            Context::url(),
            Context::request()->post()
        ]));
    }

    public function body(): array
    {
        if ($this->body === null) {
            $this->body = [];
            $this->query->offset($this->paginator->startItem());
            $this->query->limit($this->paginator->perPage());
            foreach ($this->query->fetchAll() as $item) {
                $this->body[] = ($this->callback)($item, $this);
            }
        }
        return $this->body;
    }
}
