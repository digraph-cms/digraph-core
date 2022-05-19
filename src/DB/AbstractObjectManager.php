<?php

namespace DigraphCMS\DB;

use Envms\FluentPDO\Query;
use ReflectionClass;

abstract class AbstractObjectManager
{
    const TABLE = null;
    const INSERT_COLUMNS = [];
    const UPDATE_COLUMNS = [];
    const SELECT_CLASS = AbstractObjectSelect::class;

    public static function select(): AbstractObjectSelect
    {
        $class = static::SELECT_CLASS;
        return new $class(
            static::query()->from(static::TABLE)
        );
    }

    public static function insert(object $obj): bool
    {
        $query = static::query()->insertInto(
            static::TABLE,
            static::objectColumnValues($obj, static::INSERT_COLUMNS)
        );
        return !!$query->execute();
    }

    public static function update(object $obj): bool
    {
        $query = static::query()->update(
            static::TABLE,
            static::objectColumnValues($obj, static::UPDATE_COLUMNS),
            static::objectID($obj)
        );
        return !!$query->execute();
    }

    public static function delete(object $obj): bool
    {
        $query = static::query()->delete(
            static::TABLE,
            static::objectID($obj)
        );
        return !!$query->execute();
    }

    protected static function query(): Query
    {
        return DB::query();
    }

    protected static function objectID(object $obj): int
    {
        if (method_exists($obj, 'id')) {
            return $obj->id();
        }
        $arr = (array)$obj;
        return @$arr[chr(0) . '*' . chr(0) . 'id'];
    }

    protected static function objectColumnValues(object $obj, array $columns): array
    {
        $output = [];
        $arr = (array)$obj;
        foreach ($columns as $name) {
            $output[$name] = @$arr[chr(0) . '*' . chr(0) . $name];
        }
        return $output;
    }
}
