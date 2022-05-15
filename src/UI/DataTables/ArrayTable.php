<?php

namespace DigraphCMS\UI\DataTables;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\WriterAbstract;

class ArrayTable extends AbstractPaginatedTable
{
    /**
     * Undocumented function
     *
     * @param ArrayAccess|Countable|array $array
     * @param callable $callback
     * @param array $headers
     */
    public function __construct($array, callable $callback, array $headers = [])
    {
        parent::__construct(count($array));
        $this->array = $array;
        $this->callback = $callback;
        $this->headers = $headers;
    }

    function writeDownloadFile(WriterAbstract $writer)
    {
        // write entire array into rows
        foreach ($this->array as $r) {
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

    public function body(): array
    {
        static $body;
        if ($body === null) {
            $body = [];
            $slice = array_slice(
                $this->array,
                $this->paginator->startItem(),
                $this->paginator->perPage(),
                true
            );
            foreach ($slice as $key => $item) {
                $body[] = ($this->callback)($key, $item, $this);
            }
        }
        return $body;
    }
}
