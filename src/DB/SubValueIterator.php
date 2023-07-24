<?php

namespace DigraphCMS\DB;

use ArrayIterator;
use Envms\FluentPDO\Queries\Select;
use Iterator;
use PDOStatement;

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
        // turn fpdo Select into a PDOStatement
        if ($iterator instanceof Select) $iterator = $iterator->getIterator();
        // turn PDOStatement into ArrayIterator if it lacks the rewind method
        if ($iterator instanceof PDOStatement && !method_exists($iterator,'rewind')) {
            $iterator = new ArrayIterator($iterator->fetchAll());
        }
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