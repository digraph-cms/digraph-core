<?php

namespace DigraphCMS\UI;

use DigraphCMS\Cache\UserCacheNamespace;
use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\Users;

class ActionMenu
{
    protected $url, $user, $cache;

    public function __construct(URL $url, $user = false)
    {
        $this->url = clone $url;
        $this->user = $user;
        $this->cache = new UserCacheNamespace('action-menu');
    }

    public function __toString()
    {
        return $this->cache->get(
            md5(serialize([$this->url, $this->user])),
            function () {
                ob_start();
                echo "<nav class='action-menu'><h1>Action menu</h1>";
                // output buffer contents separately
                ob_start();
                $this->printUserActions();
                $this->printPageActions();
                $this->printStaticActions();
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

    protected function printUserActions()
    {
        if (!$this->user) {
            return;
        }
        $user = Users::current();
        if ($user) {
            echo "<div class='user-actions user-actions-signedin'><h2>$user</h2><nav><ul>";
            echo "<li class='profile-link'><a href='" . $user->profile() . "'>My profile</a></li>";
            foreach (Router::staticActions('user') as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "<li class='signout-link'>" . Users::signoutUrl()->html(['signout-link']) . "</li>";
            echo "</ul></nav></div>";
        } elseif (Config::get('ui.action-menu.guestui')) {
            $user = Users::guest();
            echo "<div class='user-actions user-actions-guest'><h2>$user</h2><nav><ul>";
            if ($this->url->route() != 'signin') {
                echo "<li class='signin-link'>" . Users::signinUrl()->html(['signin-link']) . "</li>";
            }
            foreach (Router::staticActions('guest') as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</ul></nav></div>";
        }
    }

    protected function printPageActions()
    {
        if (!$this->url->page()) {
            return;
        }
        $actions = array_filter(
            Router::pageActions($this->url->page()),
            function (URL $url) {
                return
                    substr($url->action(), 0, 1) != '_'
                    && ($url->route() == $this->url->route()
                        || $this->url->route() == 'home');
            }
        );
        $addable = array_filter(
            array_map(
                function (string $type) {
                    $url = $this->url->page()->url_add($type);
                    return $url->permissions() ? "<a href='$url'>$type</a>" : false;
                },
                $this->url->page()->addableTypes()
            )
        );
        if ($actions || $addable) {
            echo "<div class='page-actions'><h2>" . $this->url->page()->url()->html() . "</h2><nav><ul>";
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            if ($addable) {
                echo "<li class='dropdown'>";
                echo "<a class='adder' tabindex='0'>Add child</a>";
                echo "<ul>";
                foreach ($addable as $link) {
                    echo "<li>$link</li>";
                }
                echo "</ul>";
                echo "</li>";
            }
            echo "</ul></nav></div>";
        }
    }

    protected function printStaticActions()
    {
        $actions = array_filter(
            Router::staticActions($this->url->route()),
            function (URL $url) {
                return
                    substr($url->action(), 0, 1) != '_'
                    && $url->route() == $this->url->route();
            }
        );
        if ($actions) {
            echo "<div class='static-actions'>";
            if (Router::staticRouteExists($this->url->route(), 'index')) {
                $title = (new URL('/~' . $this->url->route()))->html();
                echo "<h2>$title</h2>";
            }
            echo "<nav><ul>";
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</ul></nav></div>";
        }
    }
}
