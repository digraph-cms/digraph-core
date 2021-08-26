<?php

namespace DigraphCMS\Users;

use DigraphCMS\Events\Dispatcher;
use DigraphCMS\URL\URL;

class Permissions
{
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
        return
            Dispatcher::firstValue("onStaticUrlPermissions", [$url, $user]) ??
            Dispatcher::firstValue("onStaticUrlPermissions_" . $url->route(), [$url, $user]) ??
            true;
    }

    /**
     * Determine whether the user is a member of the given group.
     *
     * @param string $group
     * @param User $user
     * @return boolean
     */
    public static function inGroup(string $group, User $user): bool
    {
        foreach ($user->groups() as $g) {
            if ($g->name() == $group) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user is a member of one of the specified groups.
     *
     * @param string[] $groups
     * @param User $user
     * @return boolean
     */
    public static function inGroups(array $groups, User $user): bool
    {
        foreach ($groups as $group) {
            if (static::inGroup($group, $user)) {
                return true;
            }
        }
        return false;
    }
}
