<?php

namespace DigraphCMS;

use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class CoreEventSubscriber
{
    /**
     * Limits access to ~user route to signed-in users only
     *
     * @param URL $url
     * @param User $user
     * @return boolean|null
     */
    public static function onStaticUrlPermissions_user(URL $url, User $user): ?bool
    {
        return Permissions::inGroup('users', $user);
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
}
