<?php

namespace DigraphCMS\DB;

use ArrayIterator;
use Countable;
use DigraphCMS\Cache\Cache;
use DigraphCMS\Events\Dispatcher;
use Envms\FluentPDO\Queries\Select;
use Iterator;
use PDOStatement;

/**
 * Wraps an FPDO Select object in an analogous interface, but converts results
 * into objects on the fly.
 * 
 * @implements Iterator<int,array|object>
 */
abstract class AbstractMappedSelect implements Iterator, Countable
{
    /** @var Select */
    protected $query;
    /** @var \ArrayIterator<int,array<string,mixed>|object>|\PDOStatement */
    protected $iterator;
    /** @var bool */
    protected $returnDataObjects = true;
    /** @var string|null */
    protected $returnObjectClass = null;

    /**
     * @param array<string,mixed> $row
     * @return object|null
     */
    protected function doRowToObject(array $row): ?object
    {
        return null;
    }

    /**
     * Group this query by the given query, and return an array of copies of it
     * that each have an added subquery for each unique value in $column
     *
     * @param string $column
     * @return SubQuery[]
     */
    public function subqueries(string $column): array
    {
        $column = static::parseJsonRefs($column);
        $q = clone $this->query();
        $q->asObject(false);
        $q->group($column);
        $q->select("$column as v");
        $class = get_called_class();
        return array_map(
            function (array $row) use ($class, $column): SubQuery {
                return new SubQuery(
                    $row['v'],
                    /** @psalm-suppress UnsafeInstantiation */
                    (new $class(clone $this->query()))
                        ->where($column, $row['v'])
                );
            },
            $q->fetchAll()
        );
    }

    /**
     * @param mixed $row
     * @return object|null
     */
    protected function rowToObject($row): ?object
    {
        return is_array($row) ? $this->doRowToObject($row) : null;
    }

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
        if ($this->returnObjectClass) {
            $this->returnDataObjects = false;
            // @phpstan-ignore-next-line
            $this->query->asObject($this->returnObjectClass);
        }
    }

    public function __clone()
    {
        if (is_object($this->query)) {
            $this->query = clone $this->query;
        }
    }

    public function query(): Select
    {
        return $this->query;
    }

    /**
     * Get the name of the table this query is selecting from.
     *
     * @return string|null
     */
    public function getFromTable()
    {
        return $this->query->getFromTable();
    }

    /**
     * Specify which columns to select, optionally overriding defaults
     *
     * @param string $columns
     * @param boolean $overrideDefault
     * @return static
     */
    public function select($columns, bool $overrideDefault = false)
    {
        $columns = $this->parseJsonRefs($columns);
        if ($overrideDefault) {
            $this->returnDataObjects = false;
        }
        $this->query->select($columns, $overrideDefault);
        return $this;
    }

    /**
     * @param string $statement
     * @return static
     */
    public function leftJoin(string $statement)
    {
        $statement = $this->parseJsonRefs($statement);
        $this->query->leftJoin($statement);
        return $this;
    }

    public static function parseJsonRefs(?string $string): ?string
    {
        if ($string === null) return null;
        return preg_replace_callback(
            '/\$\{((.+?):)?([^\.\}\\\]+?)\.([^\}\\\]+?)\}/',
            function ($matches) {
                return Cache::get(
                    'db/jsonref/' . md5($matches[0]),
                    function () use ($matches) {
                        return Dispatcher::firstValue(
                            'onDbExpandJsonPath_' . DB::driver(),
                            [$matches[4], $matches[3], $matches[2]]
                        );
                    }
                );
            },
            $string
        );
    }

    /**
     * Add to the WHERE clause, defaulting to appending with "AND"
     *
     * @param string|array<mixed,string>|null $condition
     * @param string|int|float|array<int|string,string|int|float> $parameters
     * @param string $separator
     * @return static
     */
    public function where($condition, $parameters = [], string $separator = "AND")
    {
        $condition = $this->parseJsonRefs($condition);
        $this->query->where($condition, $parameters, $separator);
        return $this;
    }

    /**
     * Automatically prepare a LIKE where clause
     *
     * @param string $column
     * @param string $pattern
     * @param boolean $wildCardBefore
     * @param boolean $wildCardAfter
     * @param string $separator
     * @return static
     */
    public function like(string $column, string $pattern, bool $wildCardBefore = true, bool $wildCardAfter = true, $separator = "AND")
    {
        $parsedColumn = $this->parseJsonRefs($column);
        if ($parsedColumn != $column) {
            $parsedColumn = "LOWER($parsedColumn)";
            $pattern = strtolower($pattern);
        }
        return $this->where(
            "$parsedColumn LIKE ?",
            [static::prepareLikePattern($pattern, $wildCardBefore, $wildCardAfter)],
            $separator
        );
    }

    /**
     * Automatically prepare a NOT LIKE where clause
     *
     * @param string $column
     * @param string $pattern
     * @param boolean $wildCardBefore
     * @param boolean $wildCardAfter
     * @param string $separator
     * @return static
     */
    public function notLike(string $column, string $pattern, bool $wildCardBefore = true, bool $wildCardAfter = true, $separator = "AND")
    {
        $parsedColumn = $this->parseJsonRefs($column);
        if ($parsedColumn != $column) {
            $parsedColumn = "LOWER($parsedColumn)";
            $pattern = strtolower($pattern);
        }
        return $this->where(
            "$parsedColumn NOT LIKE ?",
            [static::prepareLikePattern($pattern, $wildCardBefore, $wildCardAfter)],
            $separator
        );
    }

    /**
     * Escape % and _ characters and wrap pattern in % at front or back as needed.
     *
     * @param string $pattern
     * @param boolean $wildCardBefore
     * @param boolean $wildCardAfter
     * @return string
     */
    public static function prepareLikePattern(string $pattern, bool $wildCardBefore = true, bool $wildCardAfter = true): string
    {
        $pattern = preg_replace('/^[^a-z0-9_\- ]+/i', '', $pattern);
        $pattern = preg_replace('/[^a-z0-9_\- ]+$/i', '', $pattern);
        $pattern = strtolower($pattern);
        return implode('', [
            $wildCardBefore ? '%' : '',
            preg_replace('/[%_]/', '\\$0', $pattern),
            $wildCardAfter ? '%' : '',
        ]);
    }

    /**
     * Shorthand to add WHERE with an "OR" separator
     *
     * @param string|array<mixed,string> $condition
     * @param string|int|float|array<int|string,string|int|float> $parameters
     * @return static
     */
    public function whereOr($condition, $parameters = [])
    {
        $condition = $this->parseJsonRefs($condition);
        $this->query->whereOr($condition, $parameters);
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
     * @return static
     */
    public function order(?string $column)
    {
        $column = $this->parseJsonRefs($column);
        $this->query->order($column);
        return $this;
    }

    /**
     * Add a HAVING clause
     *
     * @param string $statement
     * @return static
     */
    public function having(string $statement)
    {
        $this->query->having($statement);
        return $this;
    }

    /**
     * Add a LIMIT clause
     *
     * @param integer $n
     * @return static
     */
    public function limit(int $n)
    {
        $this->query->limit($n);
        return $this;
    }

    /**
     * Add an OFFSET clause
     *
     * @param integer $n
     * @return static
     */
    public function offset(int $n)
    {
        $this->query->offset($n);
        return $this;
    }

    /**
     * Add a GROUP BY clause
     *
     * @param string $column
     * @return static
     */
    public function group(string $column)
    {
        $column = $this->parseJsonRefs($column);
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
     * @return mixed
     */
    public function fetch()
    {
        if ($this->returnDataObjects) {
            return static::rowToObject($this->query->fetch());
        } else {
            return ($out = $this->query->fetch())
                ? $out : null;
        }
    }

    /**
     * Fetch all DataObjects, or raw rows if returnDataObjects is false
     *
     * @param string $index
     * @param string $selectOnly
     * @return array<int,array<string,mixed>>|array<int,object>
     */
    public function fetchAll($index = '', $selectOnly = '')
    {
        if ($this->returnDataObjects) {
            $out = [];
            foreach ($this->query->fetchAll($index, $selectOnly) as $result) {
                $out[] = static::rowToObject($result) ?? false;
            }
            return array_filter($out);
        } else {
            return $this->query->fetchAll();
        }
    }

    /**
     * Fetch pairs
     *
     * @param string $key
     * @param string $value
     * @param bool $object
     * @return array<string,mixed>|\PDOStatement
     */
    public function fetchPairs(string $key, string $value, bool $object = false)
    {
        return $this->query->fetchPairs($key, $value, $object);
    }

    /**
     * Build an iterator to use for passing through calls to \Iterable
     * interface methods
     *
     * @return \ArrayIterator<int,array<string,mixed>|object>|\PDOStatement
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

    public function count(): int
    {
        return $this->query->count();
    }

    public function current(): mixed
    {
        if ($this->returnDataObjects) {
            return static::rowToObject($this->getIterator()->current());
        } else {
            return $this->getIterator()->current();
        }
    }

    public function key(): mixed
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