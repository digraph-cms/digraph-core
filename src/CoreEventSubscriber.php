<?php

namespace DigraphCMS;

use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class CoreEventSubscriber
{
    /**
     * Limits access to ~user route to signed-in users only, unless user is 
     * specified in an arg, in which case it limits to that user or admins
     *
     * @param URL $url
     * @param User $user
     * @return boolean|null
     */
    public static function onStaticUrlPermissions_user(URL $url, User $user): ?bool
    {
        if ($url->arg('user')) {
            return
                $url->arg('user') == $user->uuid()
                || Permissions::inGroup('admins', $user);
        }
        return Permissions::inGroup('users', $user);
    }

    /**
     * Make the name of group URLs the group's name
     *
     * @param URL $url
     * @param boolean $inPageContext
     * @return string|null
     */
    public static function onStaticUrlName_groups(URL $url, bool $inPageContext): ?string
    {
        if ($group = Users::group($url->action())) {
            return $group->name();
        }
        return null;
    }

    /**
     * Make the name of user profile URLs the user's name
     *
     * @param URL $url
     * @param boolean $inPageContext
     * @return string|null
     */
    public static function onStaticUrlName_users(URL $url, bool $inPageContext): ?string
    {
        if (strlen($url->action()) == 36 && $user = Users::get($url->action())) {
            return $user->name();
        }
        return null;
    }

    public static function onStaticUrlName_signin(): ?string
    {
        return "Sign in or sign up";
    }

    /**
     * Remove all static actions from signin path
     *
     * @param URL[] $urls
     * @return void
     */
    public static function onStaticActions_signin(array &$urls)
    {
        $urls = [];
    }

    /**
     * Remove all static actions from signout path
     *
     * @param URL[] $urls
     * @return void
     */
    public static function onStaticActions_signout(array &$urls)
    {
        $urls = [];
    }
}
