<?php

namespace DigraphCMS\DB;

use DateTime;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;

abstract class AbstractDataObjectSource
{
    const TABLE = null;

    protected static $insert = [];
    protected static $update = [];
    protected static $delete = [];
    protected static $cache = [];

    abstract protected static function objectClass(array $result): string;

    public static function __init()
    {
        Dispatcher::addSubscriber(static::class);
    }

    protected static function deleteObject(AbstractDataObject $object)
    {
        $query = DB::query()->delete('pages');
        $query->where('uuid = ? AND updated = ?', [$object->uuid(), $object->updatedLast()->format("Y-m-d H:i:s")])
            ->limit(1)
            ->execute();
    }

    protected static function insertObject(AbstractDataObject $object)
    {
        $query = DB::query();
        $query->insertInto(
            'pages',
            [
                'uuid' => $object->uuid(),
                'class' => $object->class(),
                'data' => json_encode($object->get()),
                'created_by' => $object->createdBy(),
                'updated_by' => $object->updatedBy(),
            ]
        )->execute();
    }

    protected static function updateObject(AbstractDataObject $object)
    {
        $query = DB::query()->update('pages');
        $query->where('uuid = ? AND updated = ?', [$object->uuid(), $object->updatedLast()->format("Y-m-d H:i:s")])
            ->limit(1)
            ->set([
                'data' => json_encode($object->get()),
                'class' => $object->class(),
                'updated_by' => Session::user()
            ])
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
                ->where('uuid = ?', [$uuid]);
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
        static::$insert[$object->uuid()] = $object;
    }

    public static function update(AbstractDataObject $object)
    {
        static::$update[$object->uuid()] = $object;
    }

    public static function delete(AbstractDataObject $object)
    {
        static::$delete[$object->uuid()] = $object;
    }

    public static function resultToObject(array $result): AbstractDataObject
    {
        if (isset(static::$cache[$result['uuid']])) {
            return static::$cache[$result['uuid']];
        }
        if (false === ($data = json_decode($result['data'], true))) {
            throw new \Exception("Error decoding Data Object json data");
        }
        $class = static::objectClass($result);
        static::$cache[$result['uuid']] = new $class(
            $data,
            $result['uuid'],
            new DateTime($result['created']),
            $result['created_by'],
            new DateTime($result['updated']),
            $result['updated_by'],
        );
        return static::$cache[$result['uuid']];
    }
}
