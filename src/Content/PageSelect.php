<?php

namespace DigraphCMS\Content;

use ArrayIterator;
use Envms\FluentPDO\Queries\Select;
use PDOStatement;

class PageSelect implements \Countable, \Iterator
{
    protected $query, $iterator;
    protected $returnDataObjects = true;

    /**
     * Construct using a FPDO query and the class of the DataObjectSource that
     * spawned this query.
     *
     * @param Select $query
     * @param string $source
     */
    public function __construct(Select $query, string $source)
    {
        $this->query = $query;
        $this->query->disableSmartJoin();
        $this->source = $source;
    }

    /**
     * Specify which columns to select, optionally overriding defaults
     *
     * @param string $columns
     * @param boolean $overrideDefault
     * @return PageSelect
     */
    public function select($columns, bool $overrideDefault = false): PageSelect
    {
        if ($overrideDefault) {
            $this->returnDataObjects = false;
        }
        $this->query->select($columns, $overrideDefault);
        return $this;
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
     * @return PageSelect
     */
    public function where($condition, $parameters = [], $separator = "AND"): PageSelect
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
     * @return PageSelect
     */
    public function whereOr($condition, $parameters = [], $separator = "AND"): PageSelect
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
     * Add an ORDER BY clause
     *
     * @param string $column
     * @return PageSelect
     */
    public function order(string $column): PageSelect
    {
        $this->query->order($column);
        return $this;
    }

    /**
     * Add a HAVING clause
     *
     * @param string $column
     * @return PageSelect
     */
    public function having(string $column): PageSelect
    {
        $this->query->having($column);
        return $this;
    }

    /**
     * Add a LIMIT clause
     *
     * @param integer $column
     * @return PageSelect
     */
    public function limit(int $column): PageSelect
    {
        $this->query->limit($column);
        return $this;
    }

    /**
     * Add an OFFSET clause
     *
     * @param integer $column
     * @return PageSelect
     */
    public function offset(int $column): PageSelect
    {
        $this->query->offset($column);
        return $this;
    }

    /**
     * Add a GROUP BY clause
     *
     * @param string $column
     * @return PageSelect
     */
    public function group(string $column): PageSelect
    {
        $this->returnDataObjects = false;
        $this->query->group($column);
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
        return $this->query->fetchColumn($columnNumber);
    }

    /**
     * Fetch first DataObject, or raw row if returnDataObjects
     *
     * @return Page|array|null
     */
    public function fetch()
    {
        if ($this->returnDataObjects) {
            return ($this->source)::resultToPage($this->query->fetch());
        } else {
            $this->query->fetch();
        }
    }

    /**
     * Fetch all DataObjects, or raw rows if returnDataObjects
     *
     * @param string $index
     * @param string $selectOnly
     * @return array
     */
    public function fetchAll($index = '', $selectOnly = '')
    {
        if ($this->returnDataObjects) {
            $out = [];
            foreach ($this->query->fetchAll($index, $selectOnly) as $result) {
                $out[] = ($this->source)::resultToPage($result) ?? false;
            }
            return array_filter($out);
        } else {
            return $this->query->fetchAll();
        }
    }

    /**
     * Fetch pairs
     *
     * @param $key
     * @param $value
     * @param boolean $object
     * @return array|\PDOStatement
     */
    public function fetchPairs($key, $value, $object = false)
    {
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

    public function count()
    {
        return $this->query->count();
    }

    public function current()
    {
        if ($this->returnDataObjects) {
            return ($this->source)::resultToPage($this->getIterator()->current());
        } else {
            $this->getIterator()->current();
        }
    }

    public function key()
    {
        return $this->getIterator()->key();
    }

    public function next()
    {
        $this->getIterator()->next();
    }

    public function rewind()
    {
        $this->getIterator()->rewind();
    }

    public function valid(): bool
    {
        return $this->getIterator()->valid();
    }
}
