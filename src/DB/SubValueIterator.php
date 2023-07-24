<?php

namespace DigraphCMS\DB;

use Envms\FluentPDO\Queries\Select;
use Iterator;

class SubValueIterator implements Iterator
{
    /** @var callable */
    protected $column;
    /** @var Iterator */
    protected $iterator;

    public function __construct(
        Iterator|Select $iterator,
        string|callable $column
    ) {
        if (is_string($column)) $column = fn($i) => $i[$column];
        $this->column = $column;
        if ($iterator instanceof Select) $iterator = $iterator->getIterator();
        $this->iterator = $iterator;
    }

    protected function getValue(mixed $value): mixed
    {
        return call_user_func($this->column, $value);
    }

    public function current(): mixed
    {
        return $this->getValue($this->iterator->current());
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function key(): int
    {
        return $this->iterator->key();
    }
}