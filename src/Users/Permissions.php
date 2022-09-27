<?php

namespace DigraphCMS\Users;

use DigraphCMS\Config;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Session;
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
            false;
    }

    protected  static function staticUrl(URL $url, User $user): bool
    {
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

    public static function requireAuth()
    {
        if (Session::user() === null) {
            throw new HttpError(401);
        }
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
        $split = explode('__', $name, 2);
        $activity = $split[0];
        $level = @$split[1];
        if ($level === 'admin') {
            // admins are part of all admin level metagroups
            $groups[] = 'admins';
        } elseif ($level === 'edit') {
            // editors are part of all edit level metagroups
            $groups[] = 'editors';
            // matching admin level metagroup is also part of all edit level metagroups
            $groups = array_merge($groups, static::metaGroup($activity . '__admin'));
        } elseif ($level) {
            // matching admin level metagroup is also part of all level-specified metagroups
            $groups = array_merge($groups, static::metaGroup($activity . '__admin'));
            // matching editor level metagroup is also part of all level-specified metagroups
            $groups = array_merge($groups, static::metaGroup($activity . '__edit'));
        }
        return array_unique($groups);
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
