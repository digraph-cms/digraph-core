<?php

namespace DigraphCMS\UI;

use DigraphCMS\Cache\UserCacheNamespace;
use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

class UserMenu extends ActionMenu
{
    public function __construct(URL $url)
    {
        $this->url = $url;
        $this->user = true;
        $this->cache = new UserCacheNamespace('user-menu/' . (Session::user() ?? 'guest'));
    }

    protected function printUserActions()
    {
        if (!$this->user) {
            return;
        }
        $user = Users::current();
        if ($user) {
            echo "<div class='action-menu__user-actions action-menu__user-actions--signedin'><h2>$user</h2><nav><ul>";
            echo "<li class='profile-link'><a href='" . $user->profile() . "'>My profile</a></li>";
            $actions = array_filter(
                Router::staticActions('user'),
                function (URL $url) {
                    return substr($url->action(), 0, 1) != '_';
                }
            );
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "<li class='signout-link'>" . Users::signoutUrl()->html(['signout-link']) . "</li>";
            echo "</ul></nav></div>";
        } elseif (Config::get('ui.action-menu.guestui')) {
            $user = Users::guest();
            echo "<div class='action-menu__user-actions action-menu__user-actions--guest'><h2>$user</h2><nav><ul>";
            if ($this->url->route() != 'signin') {
                echo "<li class='signin-link'>" . Users::signinUrl()->html(['signin-link']) . "</li>";
            }
            $actions = array_filter(
                Router::staticActions('guest'),
                function (URL $url) {
                    return substr($url->action(), 0, 1) != '_';
                }
            );
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</ul></nav></div>";
        }
    }

    public function __toString()
    {
        return $this->cache->get(
            md5(serialize(Context::url())),
            function () {
                ob_start();
                $class = Session::user() ? 'signed-in' : 'guest';
                echo "<nav class='action-menu action-menu--user-menu action-menu--$class'><h1>User menu</h1>";
                // output buffer contents separately
                ob_start();
                $this->printUserActions();
                // return empty if no contents
                if (!ob_get_length()) {
                    ob_end_clean();
                    ob_end_clean();
                    return '';
                } else {
                    ob_end_flush();
                }
                // close up and return
                echo "</nav>";
                return ob_get_clean();
            }
        ) ?? '';
    }
}
