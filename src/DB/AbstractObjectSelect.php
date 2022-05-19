<?php

namespace DigraphCMS\DB;

use ArrayIterator;
use Envms\FluentPDO\Queries\Select;
use PDOStatement;

/**
 * Wraps an FPDO Select object in an analogous interface, but converts results
 * into objects on the fly.
 */
abstract class AbstractObjectSelect implements \Countable, \Iterator
{
    protected $query, $iterator;
    const OBJECT_CLASS = false;

    /**
     * Construct using a FPDO query and the class of the DataObjectSource that
     * spawned this query.
     *
     * @param Select $query
     */
    public function __construct(Select $query)
    {
        $this->query = $query;
        $this->query->disableSmartJoin();
    }

    public function leftJoin(string $table)
    {
        $this->query->leftJoin($table);
        return $this;
    }

    /**
     * Add to the WHERE clause, defaulting to appending with "AND"
     *
     * @param string|array $condition
     * @param array $parameters
     * @param string $separator
     * @return $this
     */
    public function where($condition, $parameters = [], $separator = "AND")
    {
        $this->query->where($condition, $parameters, $separator);
        return $this;
    }

    /**
     * Shorthand to add WHERE with an "OR" separator
     *
     * @param string|array $condition
     * @param array $parameters
     * @param string $separator
     * @return $this
     */
    public function whereOr($condition, $parameters = [], $separator = "AND")
    {
        $this->query->whereOr($condition, $parameters, $separator);
        return $this;
    }

    /**
     * Get the SQL that will be built by this query
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query->getQuery();
    }

    /**
     * Add an ORDER BY clause. Pass null to reset.
     *
     * @param ?string $column
     * @return $this
     */
    public function order(?string $column)
    {
        $this->query->order($column);
        return $this;
    }

    /**
     * Add a HAVING clause
     *
     * @param string $column
     * @return $this
     */
    public function having(string $column)
    {
        $this->query->having($column);
        return $this;
    }

    /**
     * Add a LIMIT clause
     *
     * @param integer $column
     * @return $this
     */
    public function limit(int $column)
    {
        $this->query->limit($column);
        return $this;
    }

    /**
     * Add an OFFSET clause
     *
     * @param integer $column
     * @return $this
     */
    public function offset(int $column)
    {
        $this->query->offset($column);
        return $this;
    }

    /**
     * Returns a single column
     *
     * @param integer $columnNumber
     * @return string
     */
    public function fetchColumn(int $columnNumber = 0)
    {
        $this->query->asObject(false);
        return $this->query->fetchColumn($columnNumber);
    }

    /**
     * Fetch first object
     *
     * @return mixed
     */
    public function fetch()
    {
        $this->query->asObject(static::OBJECT_CLASS);
        return $this->query->fetch();
    }

    /**
     * Fetch all objects
     *
     * @return array
     */
    public function fetchAll()
    {
        $this->query->asObject(static::OBJECT_CLASS);
        return $this->query->fetchAll();
    }

    /**
     * Fetch pairs
     *
     * @param string $key
     * @param string $value
     * @param boolean $object
     * @return array|\PDOStatement
     */
    public function fetchPairs($key, $value, $object = false)
    {
        $this->query->asObject(false);
        return $this->query->fetchPairs($key, $value, $object);
    }

    /**
     * Build an iterator to use for passing through calls to \Iterable
     * interface methods
     *
     * @return \ArrayIterator|\PDOStatement
     */
    protected function getIterator()
    {
        if ($this->iterator === null) {
            $this->query->asObject(static::OBJECT_CLASS);
            $this->iterator = $this->query->getIterator();
            // convert non-buffered queries into ArrayIterators, which is slower but
            // should make them work the same everywhere (this should maybe do
            // some checking other than "is it MySQL" but meh)
            if (DB::driver() != 'mysql' && $this->iterator instanceof PDOStatement) {
                $this->iterator = new ArrayIterator($this->iterator->fetchAll());
            }
        }
        return $this->iterator;
    }

    public function count(): int
    {
        return $this->query->count();
    }

    public function current()
    {
        return $this->getIterator()->current();
    }

    public function key()
    {
        return $this->getIterator()->key();
    }

    public function next(): void
    {
        $this->getIterator()->next();
    }

    /**
     * rewinding will work in the full-performance way if everything supports it
     * otherwise it will call fetchAll and convert the internal iterator into
     * an ArrayIterator.
     * 
     * This kinda gets the best of both worlds, by allowing maximum performance
     * when rewind isn't needed, but minimum memory usage when it isn't.
     *
     * @return void
     */
    public function rewind(): void
    {
        if (method_exists($this->getIterator(), 'rewind')) {
            $this->getIterator()->rewind();
        } else {
            $this->iterator = new ArrayIterator($this->getIterator()->fetchAll());
        }
    }

    public function valid(): bool
    {
        return $this->getIterator()->valid();
    }
}
