<?php

namespace DigraphCMS\UI\DataTables;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\WriterAbstract;
use ArrayAccess;

class ArrayTable extends AbstractPaginatedTable
{
    protected $array, $callback;

    /**
     * Undocumented function
     *
     * @param ArrayAccess|array $array
     * @param callable $callback
     * @param array $headers
     */
    public function __construct($array, callable $callback, array $headers = [])
    {
        parent::__construct(count($array));
        $this->array = $array;
        $this->callback = $callback;
        $this->setHeaders($headers);
    }

    function writeDownloadFile(DownloadWriter $writer)
    {
        // write entire array into rows
        foreach ($this->array as $r) {
            $writer->writeRow(call_user_func($this->downloadCallback,$r,$this));
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
