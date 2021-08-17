<?php

namespace DigraphCMS\Users;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;

class Users
{
    protected static $sources = [];
    protected static $cache = [];

    public static function randomName(): string
    {
        return Dispatcher::firstValue('onRandomName') ?? static::doRandomName();
    }

    public static function setUserID(string $uuid)
    {
        if (!static::get($uuid)) {
            throw new \Exception("Tried to set user UUID to a nonexistent user");
        }
        Session::setUser($uuid);
    }

    protected static function doRandomName(): string
    {
        static $animals;
        static $adjectives;
        if (!$animals) {
            $animals = preg_split('/[\r\n]+/', trim(file_get_contents(__DIR__ . '/animals.txt')));
        }
        if (!$adjectives) {
            $adjectives = preg_split('/[\r\n]+/', trim(file_get_contents(__DIR__ . '/adjectives.txt')));
        }
        $adjective = $adjectives[random_int(0, count($adjectives) - 1)];
        $adjective_start = substr($adjective, 0, 1);
        $animals_filtered = array_values(array_filter(
            $animals,
            function ($e) use ($adjective_start) {
                return substr($e, 0, 1) == $adjective_start;
            }
        ));
        $animal = $animals_filtered[random_int(0, count($animals_filtered) - 1)];
        return ucwords("$adjective $animal");
    }

    public static function current(): ?User
    {
        if (Session::user() == 'guest') {
            return null;
        } else {
            return static::get(Session::user());
        }
    }

    /**
     * Get the top result for a given slug/UUID. Will be fastest for UUID and
     * slug matches, but will run an additional query and search aliases if
     * UUID or slug matches are not found. A UUID match will take precedence,
     * followed by the oldest creation date slug match, followed by the oldest
     * alias match.
     *
     * @param string $uuid
     * @return User|null
     */
    public static function get(string $uuid): ?User
    {
        if (!isset(static::$cache[$uuid])) {
            static::$cache[$uuid] = self::doGet($uuid);
        }
        return static::$cache[$uuid];
    }

    public static function insert(User $user)
    {
        // insert value
        $query = DB::query();
        $query->insertInto(
            'users',
            [
                'user_uuid' => $user->uuid(),
                'user_name' => $user->name(),
                'user_data' => json_encode($user->get()),
                'created_by' => $user->createdBy(),
                'updated_by' => $user->updatedBy()
            ]
        )->execute();
    }

    protected static function doGet(string $uuid_or_slug): ?User
    {
        $query = DB::query()->from('users')
            ->where('user_uuid = ?', [$uuid_or_slug]);
        $result = $query->execute();
        if ($result && $result = $result->fetch()) {
            return static::resultToUser($result);
        } else {
            return null;
        }
    }

    public static function resultToUser($result): ?User
    {
        if (!is_array($result)) {
            return null;
        }
        if (isset(static::$cache[$result['user_uuid']])) {
            return static::$cache[$result['user_uuid']];
        }
        if (false === ($data = json_decode($result['user_data'], true))) {
            throw new \Exception("Error decoding User json data");
        }
        static::$cache[$result['user_uuid']] = new User(
            $data,
            [
                'uuid' => $result['user_uuid'],
                'name' => $result['user_name'],
                'created' => new DateTime($result['created']),
                'created_by' => $result['created_by'],
                'updated' => new DateTime($result['updated']),
                'updated_by' => $result['updated_by'],
            ]
        );
        return static::$cache[$result['user_uuid']];
    }

    public static function sources(): array
    {
        return array_map(static::class . '::source', array_keys(Config::get('users.sources')));
    }

    public static function source(string $name): ?AbstractUserSource
    {
        if (!isset(static::$sources[$name])) {
            if ($class = Config::get("users.sources.$name")) {
                static::$sources[$name] = new $class($name);
            } else {
                static::$sources[$name] = null;
            }
        }
        return static::$sources[$name];
    }
}
