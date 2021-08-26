<?php

namespace DigraphCMS\Users;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;

class Users
{
    protected static $sources = [];
    protected static $cache = [];
    protected static $null = [];

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
            $groups = [new Group('users')];
            $query = DB::pdo()->query('SELECT group_name FROM user_groups GROUP BY group_name ORDER BY count(*)');
            $query->execute();
            while ($g = $query->fetch()) {
                $groups[] = new Group($g['group_name']);
            }
        }
        return $groups;
    }

    /**
     * Get all groups a user belongs to
     *
     * @param string $user_uuid
     * @return Group[]
     */
    public static function groups(string $user_uuid): array
    {
        return array_map(
            function ($row) {
                return $row['group_name'];
            },
            DB::query()
                ->from('user_groups')
                ->select('group_name')
                ->where('group_user = ?', [$user_uuid])
                ->fetchAll()
        );
    }

    public static function signinUrl(URL $bounce = null): URL
    {
        $bounce = $bounce ?? Context::url();
        if ($bounce && $bounce->route() == 'signin') {
            $bounce = null;
        }
        if (count(static::sources()) == 1) {
            return static::sources()[0]->signinUrl($bounce);
        }
        $url = new URL('/~signin/');
        $url->arg('bounce', $bounce);
        return $url;
    }

    public static function signoutUrl(URL $bounce = null): URL
    {
        $bounce = $bounce ?? Context::url();
        if ($bounce && $bounce->path() == '~signin') {
            $bounce = null;
        }
        $url = new URL('/~signout/');
        $url->arg('bounce', $bounce);
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

    public static function null($uuid): NullUser
    {
        if ($uuid == 'guest') {
            return static::guest();
        }
        if (!isset(static::$null[$uuid])) {
            static::$null[$uuid] = new NullUser([], [
                'uuid' => $uuid,
                'name' => Users::randomName($uuid)
            ]);
        }
        return static::$null[$uuid];
    }

    public static function insert(User $user)
    {
        // insert value
        DB::query()
            ->insertInto(
                'user',
                [
                    'user_uuid' => $user->uuid(),
                    'user_name' => $user->name(),
                    'user_data' => json_encode($user->get()),
                    'created_by' => $user->createdBy()->uuid(),
                    'updated_by' => $user->updatedBy()->uuid()
                ]
            )
            ->execute();
    }

    public static function update(User $user)
    {
        // update values
        DB::query()
            ->update('user')
            ->where(
                'user_uuid = ? AND updated = ?',
                [
                    $user->uuid(),
                    $user->updatedLast()->format("Y-m-d H:i:s")
                ]
            )
            ->set([
                'user_name' => $user->name(),
                'user_data' => json_encode($user->get()),
                'updated_by' => Session::user()
            ])
            ->execute();
    }

    protected static function doGet(string $uuid_or_slug): ?User
    {
        $query = DB::query()->from('user')
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
        return array_filter(
            array_map(static::class . '::source', array_keys(Config::get('users.sources'))),
            function (?AbstractUserSource $source) {
                return $source && $source->active();
            }
        );
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
