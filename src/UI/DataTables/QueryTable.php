<?php

namespace DigraphCMS\UI\DataTables;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use DigraphCMS\Context;
use DigraphCMS\FS;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;

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

    public function downloadFile(): File
    {
        return new DeferredFile(
            $this->filename . '.ods',
            function (DeferredFile $file) {
                FS::touch($file->path());
                $writer = WriterEntityFactory::createODSWriter();
                $writer->openToFile($file->path() . '.tmp.ods');
                $query = clone $this->query;
                while ($r = $query->fetch()) {
                    $writer->addRow(WriterEntityFactory::createRow(
                        array_map(
                            function ($c) {
                                return WriterEntityFactory::createCell($c);
                            },
                            ($this->callback)($r, $this)
                        )
                    ));
                }
                $writer->close();
                FS::copy($file->path() . '.tmp.ods', $file->path());
                unlink($file->path() . '.tmp.ods');
            },
            'queryTable--' . Context::url() . '--' . $this->id()
        );
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
