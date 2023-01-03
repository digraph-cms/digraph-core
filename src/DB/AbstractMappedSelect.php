<?php

namespace DigraphCMS\DB;

use ArrayIterator;
use DigraphCMS\Cache\Cache;
use DigraphCMS\Events\Dispatcher;
use Envms\FluentPDO\Queries\Select;
use PDOStatement;
use Traversable;

/**
 * Wraps an FPDO Select object in an analogous interface, but converts results
 * into objects on the fly.
 */
abstract class AbstractMappedSelect implements \Countable, \IteratorAggregate
{
    protected $query, $iterator;
    protected $returnDataObjects = true;
    protected $returnObjectClass = null;

    protected function doRowToObject(array $row)
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
        $q->asObject(false)->group($column)->select("$column as v");
        $class = get_called_class();
        return array_map(
            function (array $row) use ($class, $column): SubQuery {
                return new SubQuery(
                    $row['v'],
                    /** @phpstan-ignore-next-line */
                    (new $class(clone $this->query()))
                        ->where($column, $row['v'])
                );
            },
            $q->fetchAll()
        );
    }

    protected function rowToObject($row)
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
            $this->query->asObject($this->returnObjectClass);
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
        if ($overrideDefault) {
            $this->returnDataObjects = false;
        }
        $this->query->select($columns, $overrideDefault);
        return $this;
    }

    public function leftJoin(string $statement)
    {
        $this->query->leftJoin($this->parseJsonRefs($statement));
        return $this;
    }

    public static function parseJsonRefs(?string $string): ?string
    {
        if ($string === null) return null;
        return preg_replace_callback(
            '/\$\{((.+):)?([^\.\}\\\]+)\.([^\}\\\]+)\}/',
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
     * @param string|array<mixed,string> $condition
     * @param mixed $parameters
     * @param string $separator
     * @return static
     */
    public function where($condition, $parameters = null, $separator = "AND")
    {
        $condition = $this->parseJsonRefs($condition);
        $this->query->where($condition, $parameters ?? [], $separator);
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
        return $this->where(
            "$column LIKE ?",
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
        return $this->where(
            "$column NOT LIKE ?",
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
     * @param mixed $parameters
     * @param string $separator
     * @return static
     */
    public function whereOr($condition, $parameters = [], $separator = "AND")
    {
        $condition = $this->parseJsonRefs($condition);
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
     * @param string $column
     * @param array|mixed $parameters
     * @return static
     */
    public function having(string $column, $parameters = [])
    {
        $this->query->having($column, $parameters);
        return $this;
    }

    /**
     * Add a LIMIT clause
     *
     * @param integer $column
     * @return static
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
     * @return static
     */
    public function offset(int $column)
    {
        $this->query->offset($column);
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
     * @return \ArrayIterator<int,object|array<mixed>>|\PDOStatement
     */
    public function getIterator(): Traversable
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
}
