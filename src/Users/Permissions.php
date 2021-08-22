<?php

namespace DigraphCMS\Users;

use DigraphCMS\HTTP\HttpError;
use DigraphCMS\URL\URL;

class Permissions
{
    public static function url(URL $url, User $user = null): ?bool
    {
        if ($url->page()) {
            return static::pageUrl($url, $user);
        } else {
            return static::staticUrl($url, $user);
        }
    }
    public static function pageUrl(URL $url, User $user = null): ?bool
    {
        // TODO: figure out how config works
        return null;
    }

    public static function staticUrl(URL $url, User $user = null): ?bool
    {
        // TODO: figure out how config works
        return null;
    }

    /**
     * Determine whether the user is a member of the given group.
     *
     * @param string $group
     * @param User $user
     * @return boolean
     */
    public static function inGroup(string $group, User $user = null): bool
    {
        $user = $user ?? Users::current() ?? Users::guest();
        foreach ($user->groups() as $group) {
            if ($group->name() == $group) {
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
    public static function inGroups(array $groups, User $user = null): bool
    {
        $user = $user ?? Users::current() ?? Users::guest();
        foreach ($groups as $group) {
            if (static::inGroup($group, $user)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Require that the current user be a member of a given group. Throws 
     * exception if user is not.
     *
     * @param string $group
     * @return void
     */
    public static function requireGroup(string $group)
    {
        if (!static::inGroup($group)) {
            throw new HttpError(401, "Must be a member of the group $group");
        }
    }

    /**
     * Require that the current user be a member of one of the groups given.
     * Throws an exception if the user is not. 
     *
     * @param string[] $groups
     * @param User $user
     * @return void
     */
    public static function requireGroups(array $groups)
    {
        if (!static::inGroups($groups)) {
            throw new HttpError(401, "Must be a member of one of the groups: " . implode(', ', $groups));
        }
    }
}
