<?php

namespace DigraphCMS\DataObjects;

use ArrayIterator;
use Destructr\Factory;
use Destructr\Search;

/**
 * Re-implements Destructr's search class to add parameters as part of where and order
 * calls, which means that this object can implement Iterator and be looped directly
 * without needing to explicitly call execute()
 * 
 * Also keeps track of where and order clauses as arrays, allowing multiple where()
 * or order() calls to stack like in fpdo
 */
class DSOQuery implements \Countable, \Iterator
{
    protected $factory;
    protected $iterator;
    protected $search;
    protected $limit;
    protected $offset;
    protected $where = [];
    protected $order = [];
    protected $parameterCounter = 0;
    protected $includeDeleted = false;

    public function __construct(Factory $factory = null)
    {
        $this->factory = $factory;
    }

    protected function searchObject(): Search
    {
        if (!$this->search) {
            $this->search = new Search($this->factory);
            $this->search->where($this->generateWhere());
            $this->search->order($this->generateOrder());
            $this->search->limit($this->limit);
            $this->search->offset($this->offset);
        }
        return $this->search;
    }

    public function fetchAll()
    {
        return $this->searchObject()->execute($this->parameters(), $this->includeDeleted);
    }

    public function count(): int
    {
        return $this->searchObject()->count($params ?? $this->parameters(), $this->includeDeleted);
    }

    public function parameters(): array
    {
        $parameters = [];
        foreach (array_merge($this->where, $this->order) as list($c, $p)) {
            $parameters = array_merge($parameters, $p);
        }
        return $parameters;
    }

    public function generateOrder(): string
    {
        if (!$this->order) return '${created.date} ASC';
        return implode(', ', array_map(
            function ($order): string {
                return $order[0];
            },
            $this->order
        ));
    }

    public function generateWhere(): string
    {
        $where = '';
        foreach ($this->where as list($clause, $parameters, $separator)) {
            if ($where) $clause = " $separator $clause";
            $where .= $clause;
        }
        return $where;
    }

    public function includeDeleted(?bool $includeDeleted)
    {
        $this->includeDeleted = $includeDeleted;
        return $this;
    }

    public function where(?string $where, array $parameters = [], $separator = 'AND')
    {
        $this->iterator = null;
        $this->search = null;
        if ($where) $this->where[] = $this->prepareClause("($where)", $parameters, $separator);
        else $this->where = [];
        return $this;
    }

    public function order(?string $order, array $parameters = [])
    {
        $this->iterator = null;
        $this->search = null;
        if ($order) $this->order[] = $this->prepareClause($order, $parameters);
        else $this->order = [];
        return $this;
    }

    protected function prepareClause(string $clause, array $parameters, string $separator = null): array
    {
        $outputParameters = [];
        foreach ($parameters as $k => $v) {
            if (is_int($k)) {
                $k = 'dsoatp' . $this->parameterCount++;
                $clause = preg_replace('/\?/', ':' . $k, $clause, 1);
            }
            $outputParameters[$k] = $v;
        }
        return [$clause, $outputParameters, $separator];
    }

    public function limit(int $set = null)
    {
        $this->iterator = null;
        $this->search = null;
        $this->limit = $set;
        return $this;
    }

    public function offset(int $set = null)
    {
        $this->iterator = null;
        $this->search = null;
        $this->offset = $set;
        return $this;
    }

    public function current()
    {
        $this->getIterator()->current();
    }

    public function key()
    {
        return $this->getIterator()->key();
    }

    public function next(): void
    {
        $this->getIterator()->next();
    }

    public function rewind(): void
    {
        $this->getIterator()->rewind();
    }

    public function valid(): bool
    {
        return $this->getIterator()->valid();
    }

    protected function getIterator(): ArrayIterator
    {
        return $this->iterator
            ?? $this->iterator = new ArrayIterator($this->execute());
    }
}
