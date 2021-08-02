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

    protected static $insert = [];
    protected static $update = [];
    protected static $delete = [];
    protected static $cache = [];
    protected static $exists = [];

    abstract public static function objectClass(array $result): string;

    public static function __init()
    {
        Dispatcher::addSubscriber(static::class);
    }

    public static function exists(string $uuid): bool
    {
        if (!isset(self::$exists[$uuid])) {
            self::$exists[$uuid] = self::doExists($uuid);
        }
        return self::$exists[$uuid];
    }

    protected static function doExists(string $uuid): bool
    {
        $query = DB::query()->from(static::TABLE)
            ->where(static::COLNAMES['uuid'] . ' = ?', [$uuid])
            ->limit(1);
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

    protected static function deleteObject(AbstractDataObject $object)
    {
        $query = DB::query()->delete(static::TABLE);
        $query->where(
            static::COLNAMES['uuid'] . ' = ? AND ' . static::COLNAMES['updated'] . ' = ?',
            [
                $object->uuid(),
                $object->updatedLast()->format("Y-m-d H:i:s")
            ]
        )->execute();
    }

    protected static function insertObject(AbstractDataObject $object)
    {
        $query = DB::query();
        $query->insertInto(
            static::TABLE,
            static::insertObjectValues($object)
        )->execute();
    }

    protected static function updateObject(AbstractDataObject $object)
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

    public static function onDatabaseCommit()
    {
        // do deletions
        foreach (static::$delete as $uuid => $object) {
            static::deleteObject($object);
            unset(static::$cache[$uuid]);
            unset(static::$delete[$uuid]);
            unset(static::$insert[$uuid]);
            unset(static::$update[$uuid]);
        }
        // do insertions
        foreach (static::$insert as $uuid => $object) {
            static::insertObject($object);
            unset(static::$insert[$uuid]);
        }
        // do updates
        foreach (static::$update as $uuid => $object) {
            static::updateObject($object);
            unset(static::$update[$uuid]);
        }
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

    public static function insert(AbstractDataObject $object)
    {
        DB::TOUCH;
        static::$insert[$object->uuid()] = $object;
    }

    public static function update(AbstractDataObject $object)
    {
        DB::TOUCH;
        static::$update[$object->uuid()] = $object;
    }

    public static function delete(AbstractDataObject $object)
    {
        DB::TOUCH;
        static::$delete[$object->uuid()] = $object;
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
