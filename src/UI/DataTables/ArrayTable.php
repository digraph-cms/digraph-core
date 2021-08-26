<?php

namespace DigraphCMS\UI\DataTables;


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
