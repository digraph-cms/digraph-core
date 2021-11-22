<?php

namespace DigraphCMS\UI\DataLists;


class ArrayList extends AbstractPaginatedList
{
    /**
     * Undocumented function
     *
     * @param ArrayAccess|Countable|array $array
     * @param callable $callback
     */
    public function __construct($array, callable $callback)
    {
        parent::__construct(count($array));
        $this->array = $array;
        $this->callback = $callback;
    }

    public function items(): array
    {
        static $items;
        if ($items === null) {
            $items = [];
            $slice = array_slice(
                $this->array,
                $this->paginator->startItem(),
                $this->paginator->perPage(),
                true
            );
            foreach ($slice as $key => $item) {
                $items[] = ($this->callback)($key, $item, $this);
            }
        }
        return $items;
    }
}
