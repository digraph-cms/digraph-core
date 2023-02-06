<?php

namespace DigraphCMS\Users;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;

abstract class Users
{
    protected static $sources = [];
    protected static $cache = [];
    protected static $null = [];

    /**
     * Return all provider IDs associated with a given user for the specified
     * user source/provider.
     *
     * @param string $userUUID
     * @param string $source
     * @param string $provider
     * @return string[]
     */
    public static function providerIDs(string $userUUID, string $source, string $provider): array
    {
        return array_map(
            function ($row) {
                return $row['provider_id'];
            },
            DB::query()->from('user_source')
                ->where('user_uuid = ?', [$userUUID])
                ->where('source = ?', [$source])
                ->where('provider = ?', [$provider])
                ->fetchAll()
        );
    }

    /**
     * Locate the user specified by a given source/provider/id, if it exists.
     *
     * @param string $source
     * @param string $provider
     * @param string $id
     * @return User|null
     */
    public static function locateUser(string $source, string $provider, string $id): ?User
    {
        $result = DB::query()->from('user_source')
            ->leftJoin('user on user_source.user_uuid = user.uuid')
            ->where('source = ?', [$source])
            ->where('provider = ?', [$provider])
            ->where('provider_id = ?', [$id])
            ->limit(1)
            ->fetch();
        return $result
            ? static::resultToUser($result)
            : null;
    }

    /**
     * Get an array of all possible signin URLs, so that if there are more than
     * one options can be presented to the user.
     *
     * @param string|null $bounce
     * @return URL[]
     */
    public static function allSigninURLs(string $bounce = null): array
    {
        static $urls;
        if (!$urls) {
            $urls = [];
            foreach (static::sources() as $source) {
                $urls = array_merge($urls, $source->allSigninURLs($bounce));
            }
        }
        return $urls;
    }

    /**
     * Generate a UserSelect object for building queries to the pages table
     *
     * @return UserSelect
     */
    public static function select(): UserSelect
    {
        return new UserSelect(
            DB::query()->from('user')
        );
    }

    /**
     * Get a list of all groups
     *
     * @return Group[]
     */
    public static function allGroups(): array
    {
        static $groups;
        if (!$groups) {
            $groups = [new Group('users', 'All users')];
            $query = DB::query()->from('user_group');
            while ($g = $query->fetch()) {
                $groups[] = new Group($g['uuid'], $g['name']);
            }
        }
        return $groups;
    }

    public static function group(string $uuid): ?Group
    {
        $query = DB::query()
            ->from('user_group')
            ->where('uuid = ?', [$uuid]);
        if ($group = $query->fetch()) return new Group($group['uuid'], $group['name']);
        elseif ($uuid == 'admins') return new Group('admins', 'Administrators');
        else return null;
    }

    /**
     * Get all groups a user belongs to
     *
     * @param string $user_uuid
     * @return Group[]
     */
    public static function groups(string $user_uuid): array
    {
        $groups = array_map(
            function ($group) {
                return new Group($group['uuid'], $group['name']);
            },
            DB::query()
                ->from('user_group_membership')
                ->leftJoin('user_group on user_group.uuid = group_uuid')
                ->select('user_group.*')
                ->where('user_uuid = ?', [$user_uuid])
                ->fetchAll()
        );
        Dispatcher::dispatchEvent('onUserGroups', [$user_uuid, &$groups]);
        return $groups;
    }

    public static function signinUrl(URL $bounce = null): URL
    {
        $bounce = $bounce ?? Context::url();
        $url = new URL('/~signin/');
        $url->arg('_bounce', $bounce);
        return $url;
    }

    public static function signoutUrl(URL $bounce = null): URL
    {
        $bounce = $bounce ?? Context::url();
        if ($bounce->path() == '~signin') {
            $bounce = null;
        }
        $url = new URL('/~signout/');
        $url->arg('_bounce', $bounce);
        return $url;
    }

    public static function randomName(string $seed = null): string
    {
        return Dispatcher::firstValue('onRandomName', [$seed]) ?? static::doRandomName($seed);
    }

    protected static function doRandomName(string $seed = null): string
    {
        static $animals;
        static $adjectives;
        if (!$animals) {
            $animals = preg_split('/[\r\n]+/', trim(file_get_contents(__DIR__ . '/animals.txt')));
        }
        if (!$adjectives) {
            $adjectives = preg_split('/[\r\n]+/', trim(file_get_contents(__DIR__ . '/adjectives.txt')));
        }
        // seed random generator if necessary
        if ($seed) {
            mt_srand(crc32($seed));
        } else {
            mt_srand(rand());
        }
        // generate name
        $adjective = $adjectives[mt_rand(0, count($adjectives) - 1)];
        $adjective_start = substr($adjective, 0, 1);
        $animals_filtered = array_values(array_filter(
            $animals,
            function ($e) use ($adjective_start) {
                return substr($e, 0, 1) == $adjective_start;
            }
        ));
        $animal = $animals_filtered[mt_rand(0, count($animals_filtered) - 1)];
        // return name
        return ucwords("$adjective $animal");
    }

    public static function current(): ?User
    {
        if (Session::user()) {
            return static::get(Session::user());
        } else {
            return null;
        }
    }

    /**
     * Get the top result for a given UUID
     *
     * @param string|null $uuid
     * @return User|null
     */
    public static function get(?string $uuid): ?User
    {
        if ($uuid === null) return null;
        if (!isset(static::$cache[$uuid])) {
            static::$cache[$uuid] = self::doGet($uuid);
        }
        return static::$cache[$uuid];
    }

    /**
     * Return whether or not a given user UUID exists in the database
     *
     * @param string $uuid
     * @return boolean
     */
    public static function exists(string $uuid): bool
    {
        return !!DB::query()->from('user')
            ->where('uuid = ?', [$uuid])
            ->count();
    }

    /**
     * Works fundamentally the same way as get(), but instead of returning null
     * for nonexistent users, will return a NullUser even for nonexistent users
     * which means that it can be used to safely display potentially missing
     * users.
     *
     * @param string $uuid
     * @return User
     */
    public static function user(string $uuid): User
    {
        return static::get($uuid) ?? static::null($uuid);
    }

    public static function guest(): NullUser
    {
        static $guest;
        $guest = $guest ?? new NullUser([], [
            'uuid' => 'guest',
            'name' => "Guest"
        ]);
        return $guest;
    }

    public static function system(): NullUser
    {
        static $guest;
        $guest = $guest ?? new NullUser([], [
            'uuid' => 'system',
            'name' => "System"
        ]);
        return $guest;
    }

    public static function null(string $uuid): NullUser
    {
        if ($uuid == 'guest') return static::guest();
        elseif ($uuid == 'system') return static::system();
        elseif (!isset(static::$null[$uuid])) {
            static::$null[$uuid] = new NullUser([], [
                'uuid' => $uuid,
                'name' => Users::randomName($uuid)
            ]);
        }
        return static::$null[$uuid];
    }

    public static function insert(User $user): void
    {
        // insert value
        DB::query()
            ->insertInto(
                'user',
                [
                    'uuid' => $user->uuid(),
                    'name' => $user->name(),
                    'data' => json_encode($user->get()),
                    'created' => time(),
                    'created_by' => $user->createdByUUID(),
                    'updated' => time(),
                    'updated_by' => $user->updatedByUUID()
                ]
            )
            ->execute();
    }

    public static function update(User $user): void
    {
        // update values
        DB::query()
            ->update('user')
            ->where(
                'uuid = ? AND updated = ?',
                [
                    $user->uuid(),
                    $user->updatedLast()->getTimestamp()
                ]
            )
            ->set([
                'name' => $user->name(),
                'data' => json_encode($user->get()),
                'updated' => time(),
                'updated_by' => Session::user()
            ])
            ->execute();
    }

    protected static function doGet(string $uuid): ?User
    {
        $query = DB::query()->from('user')
            ->where('uuid = ?', [$uuid]);
        $result = $query->execute();
        if ($result = $result->fetch()) {
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
        if (isset(static::$cache[$result['uuid']])) {
            return static::$cache[$result['uuid']];
        }
        if (false === ($data = json_decode($result['data'], true))) {
            throw new \Exception("Error decoding User json data");
        }
        static::$cache[$result['uuid']] = new User(
            $data,
            [
                'uuid' => $result['uuid'],
                'name' => $result['name'],
                'created' => (new DateTime)->setTimestamp($result['created']),
                'created_by' => $result['created_by'],
                'updated' => (new DateTime)->setTimestamp($result['updated']),
                'updated_by' => $result['updated_by'],
            ]
        );
        return static::$cache[$result['uuid']];
    }

    /**
     * return all the user sources that are currently active
     *
     * @return AbstractUserSource[]
     */
    public static function sources(): array
    {
        return array_filter(
            array_map(static::class . '::source', array_keys(Config::get('users.sources'))),
            function (?AbstractUserSource $source) {
                return $source && $source->active();
            }
        );
    }

    /**
     * @param string $name
     * @return AbstractUserSource|null
     */
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
