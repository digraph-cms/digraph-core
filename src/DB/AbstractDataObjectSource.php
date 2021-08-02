<?php

namespace DigraphCMS\DB;

use DateTime;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;

abstract class AbstractDataObjectSource
{
    const TABLE = null;
    const COLNAMES = [
        'uuid' => 'uuid',
        'data' => false,
        'created' => 'created',
        'created_by' => 'created_by',
        'updated' => 'updated',
        'updated_by' => 'updated_by',
    ];

    protected static $cache = [];

    abstract public static function objectClass(array $result): string;

    public static function __init()
    {
        Dispatcher::addSubscriber(static::class);
    }

    public static function exists(string $uuid): bool
    {
        $query = DB::query()->from(static::TABLE)
            ->where(static::COLNAMES['uuid'] . ' = ?', [$uuid]);
        return !!$query->count();
    }

    public static function select(): DataObjectSelect
    {
        return new DataObjectSelect(DB::query()->from(static::TABLE), static::class);
    }

    protected static function insertObjectValues(AbstractDataObject $object): array
    {
        return [
            'uuid' => $object->uuid(),
            'data' => json_encode($object->get()),
            'created_by' => $object->createdBy(),
            'updated_by' => $object->updatedBy(),
        ];
    }

    protected static function updateObjectValues(AbstractDataObject $object): array
    {
        return [
            'data' => json_encode($object->get()),
            'updated_by' => Session::user()
        ];
    }

    public static function delete(AbstractDataObject $object)
    {
        $query = DB::query()->delete(static::TABLE);
        $query->where(
            static::COLNAMES['uuid'] . ' = ? AND ' . static::COLNAMES['updated'] . ' = ?',
            [
                $object->uuid(),
                $object->updatedLast()->format("Y-m-d H:i:s")
            ]
        )->execute();
        static::filterCache($object);
    }

    /**
     * Remove a given object from the 
     *
     * @param AbstractDataObject $object
     * @return void
     */
    protected static function filterCache(AbstractDataObject $object)
    {
        foreach (static::$cache as $i => $v) {
            if ($v->uuid() == $object->uuid()) {
                unset(static::$cache[$i]);
            }
        }
    }

    public static function insert(AbstractDataObject $object)
    {
        $query = DB::query();
        $query->insertInto(
            static::TABLE,
            static::insertObjectValues($object)
        )->execute();
    }

    public static function update(AbstractDataObject $object)
    {
        $query = DB::query()->update(static::TABLE);
        $query->where(
            static::COLNAMES['uuid'] . ' = ? AND ' . static::COLNAMES['updated'] . ' = ?',
            [
                $object->uuid(),
                $object->updatedLast()->format("Y-m-d H:i:s")
            ]
        )
            ->set(static::updateObjectValues($object))
            ->execute();
    }

    public static function get(string $uuid): ?AbstractDataObject
    {
        if (!isset(static::$cache[$uuid])) {
            $query = DB::query()->from(static::TABLE)
                ->limit(1)
                ->where(static::COLNAMES['uuid'] . ' = ?', [$uuid]);
            $result = $query->execute();
            if ($result && $result = $result->fetch()) {
                static::$cache[$uuid] = static::resultToObject($result);
            } else {
                static::$cache[$uuid] = null;
            }
        }
        return static::$cache[$uuid];
    }

    public static function resultToObject($result): ?AbstractDataObject
    {
        if (!is_array($result)) {
            return null;
        }
        if (isset(static::$cache[$result[static::COLNAMES['uuid']]])) {
            return static::$cache[$result[static::COLNAMES['uuid']]];
        }
        if (static::COLNAMES['data']) {
            if (false === ($data = json_decode($result[static::COLNAMES['data']], true))) {
                throw new \Exception("Error decoding Data Object json data");
            }
        } else {
            $data = [];
        }
        $class = static::objectClass($result);
        static::$cache[$result[static::COLNAMES['uuid']]] = new $class(
            $data,
            [
                'uuid' => $result[static::COLNAMES['uuid']],
                'created' => new DateTime($result[static::COLNAMES['created']]),
                'created_by' => $result[static::COLNAMES['created_by']],
                'updated' => new DateTime($result[static::COLNAMES['updated']]),
                'updated_by' => $result[static::COLNAMES['updated_by']],
            ]
        );
        return static::$cache[$result[static::COLNAMES['uuid']]];
    }
}
