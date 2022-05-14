<?php

namespace DigraphCMS\UI\DataTables;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\WriterAbstract;
use DigraphCMS\Context;

class QueryTable extends AbstractPaginatedTable
{
    protected $query, $callback, $headers, $body;

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

    protected function writeDownloadFile(WriterAbstract $writer)
    {
        // load all query results into rows
        $query = clone $this->query;
        while ($r = $query->fetch()) {
            $writer->addRow(WriterEntityFactory::createRow(
                array_map(
                    function ($cell) {
                        if (!($cell instanceof Cell)) $cell = WriterEntityFactory::createCell($cell);
                        return $cell;
                    },
                    ($this->downloadCallback)($r, $this)
                )
            ));
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
            while ($item = $this->query->fetch()) {
                $this->body[] = ($this->callback)($item, $this);
            }
        }
        return $this->body;
    }
}
