<?php

namespace DigraphCMS\Users;

use DigraphCMS\Config;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\URL\URL;

class Permissions
{
    /**
     * Predefined metagroups (also configurable in config option "metagroups")
     * Note that any requested metagroup ending in __admin will always include
     * the group admins and any ending in __edit will always include admins
     * and editors
     */
    const METAGROUPS = [];

    public static function url(URL $url, User $user = null): bool
    {
        $user = $user ?? Users::current() ?? Users::guest();
        if ($url->page()) {
            return static::pageUrl($url, $user);
        } else {
            return static::staticUrl($url, $user);
        }
    }

    protected  static function pageUrl(URL $url, User $user): bool
    {
        // TODO: figure out how config works, it should take priority
        // try route class events
        foreach ($url->page()->routeClasses() as $class) {
            if (null !== $value = Dispatcher::firstValue("onPageUrlPermissions_$class", [$url, $user])) {
                return $value;
            }
        }
        // try generic events, then page
        return
            Dispatcher::firstValue("onPageUrlPermissions", [$url, $user]) ??
            $url->page()->permissions($url, $user) ??
            true;
    }

    protected  static function staticUrl(URL $url, User $user): bool
    {
        // TODO: figure out how config works, it should take priority
        // try route-specific events, then generic events
        $route = explode('/', $url->route());
        $event = "onStaticUrlPermissions";
        while ($dir = array_shift($route)) {
            $event .= '_' . $dir;
            $result = Dispatcher::firstValue($event, [$url, $user]);
            if ($result !== null) {
                return $result;
            }
        }
        return
            Dispatcher::firstValue("onStaticUrlPermissions", [$url, $user]) ??
            true;
    }

    /**
     * Determine whether the user is a member of the given group.
     *
     * @param string $group
     * @param User|null $user
     * @return boolean
     */
    public static function inGroup(string $group, User $user = null): bool
    {
        $user = $user ?? Users::current() ?? Users::guest();
        foreach ($user->groups() as $g) {
            if ($g->uuid() == $group) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user is a member of one of the specified groups.
     *
     * @param string[] $groups
     * @param User|null $user
     * @return boolean
     */
    public static function inGroups(array $groups, User $user = null): bool
    {
        foreach ($groups as $group) {
            if (static::inGroup($group, $user)) {
                return true;
            }
        }
        return false;
    }

    public static function requireGroup(string $group)
    {
        if (!static::inGroup($group)) {
            throw new HttpError(401);
        }
    }

    public static function requireGroups(array $groups)
    {
        if (!static::inGroups($groups)) {
            throw new HttpError(401);
        }
    }

    public static function metaGroup(string $name): array
    {
        $groups = Config::get("metagroups.$name") ?? @static::METAGROUPS[$name] ?? [];
        if (substr($name, -7) == '__admin') {
            $groups[] = 'admins';
        }
        if (substr($name, -6) == '__edit') {
            $groups[] = 'admins';
            $groups[] = 'editors';
        }
        return $groups;
    }

    public static function requireMetaGroup(string $name)
    {
        static::requireGroups(static::metaGroup($name));
    }

    public static function inMetaGroup(string $name, User $user = null): bool
    {
        return static::inGroups(static::metaGroup($name), $user);
    }

    public static function inMetaGroups(array $groups): bool
    {
        foreach ($groups as $group) {
            if (static::inMetaGroup($group)) {
                return true;
            }
        }
        return false;
    }

    public static function requireMetaGroups(array $groups)
    {
        if (!static::inMetaGroups($groups)) {
            throw new HttpError(401);
        }
    }
}
