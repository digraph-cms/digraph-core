<?php

namespace DigraphCMS\Datastore;

use DigraphCMS\DB\DB;
use DigraphCMS\Session\Session;
use Flatrr\FlatArray;

class Datastore
{

    /**
     * Delete all records from a given namespace and optionally group, if their
     * creation date is older than the given time.
     * 
     * @param string $namespace 
     * @param null|string $group 
     * @param int $time 
     * @return void 
     */
    public static function expire(string $namespace, ?string $group, int $time): void
    {
        $query = DB::query()
            ->delete('datastore')
            ->where('created < ?', $time)
            ->where('namespace', $namespace);
        if ($group) $query->where('grp', $group);
        $query->execute();
    }

    /**
     * @param string $namespace
     * @param string $group
     * @param string $key
     * @return boolean
     */
    public static function exists(string $namespace, string $group, string $key): bool
    {
        return !!DB::query()->from('datastore')
            ->where('`ns`', $namespace)
            ->where('`grp`', $group)
            ->where('`key`', $key)
            ->count();
    }

    /**
     * Provides a more efficient way of collecting a single value when no extra
     * information is needed.
     * 
     * @param string $namespace
     * @param string $group
     * @param string $key
     * @return string|null|false
     */
    public static function value(string $namespace, string $group, string $key)
    {
        $value = DB::query()->from('datastore')
            ->where('`ns`', $namespace)
            ->where('`grp`', $group)
            ->where('`key`', $key)
            ->fetch();
        if (!$value) return false;
        else return $value['value'];
    }

    /**
     * @param string $namespace
     * @param string $group
     * @param string $key
     * @return boolean
     */
    public static function delete(string $namespace, string $group, string $key): bool
    {
        return !!DB::query()->delete('datastore')
            ->where('`ns`', $namespace)
            ->where('`grp`', $group)
            ->where('`key`', $key)
            ->execute();
    }

    /**
     * @param string $namespace
     * @param string $group
     * @param string $key
     * @return DatastoreItem|null
     */
    public static function get(string $namespace, string $group, string $key): ?DatastoreItem
    {
        return (new DatastoreSelect)
            ->where('`ns`', $namespace)
            ->where('`grp`', $group)
            ->where('`key`', $key)
            ->fetch();
    }

    /**
     * @param integer $id
     * @return DatastoreItem|null
     */
    public static function getByID(int $id): ?DatastoreItem
    {
        return (new DatastoreSelect)
            ->where('id', $id)
            ->fetch();
    }

    /**
     * Sanitize a value so that it can be displayed elsewhere safely. This is
     * meant for use sanitizing namespaces, groups, and keys. Any non-html-safe
     * characters will cause the result to be hashed so that it can be displayed
     * later safely.
     *
     * @param string $input
     * @return string
     */
    public static function sanitize(string $input): string
    {
        $sanitized = htmlspecialchars($input);
        if ($sanitized == $input) return $input;
        else return md5($input);
    }

    /**
     * @param string $namespace
     * @param string $group
     * @param string $key
     * @param string|null $value
     * @param array|FlatArray|null $data
     * @return DatastoreItem
     */
    public static function set(string $namespace, string $group, string $key, ?string $value, $data = null): DatastoreItem
    {
        $namespace = static::sanitize($namespace);
        $group = static::sanitize($group);
        $key = static::sanitize($key);
        if ($value === '') $value = null;
        if ($data instanceof FlatArray) $data = $data->get();
        if ($data === null) $data = [];
        if (static::exists($namespace, $group, $key)) {
            // update existing value
            DB::query()->update(
                'datastore',
                [
                    '`value`' => $value,
                    '`data`' => json_encode($data),
                    '`updated`' => time(),
                    '`updated_by`' => Session::uuid(),
                ]
            )
                ->where('`ns`', $namespace)
                ->where('`grp`', $group)
                ->where('`key`', $key)
                ->execute();
        } else {
            // insert new value
            DB::query()->insertInto(
                'datastore',
                [
                    '`ns`' => $namespace,
                    '`grp`' => $group,
                    '`key`' => $key,
                    '`value`' => $value,
                    '`data`' => json_encode($data),
                    '`updated`' => time(),
                    '`updated_by`' => Session::uuid(),
                    '`created`' => time(),
                    '`created_by`' => Session::uuid(),
                ]
            )->execute();
        }
        return static::get($namespace, $group, $key);
    }
}
