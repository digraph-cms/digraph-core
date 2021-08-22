<?php

namespace DigraphCMS\UI;

use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

class ActionMenu
{
    protected $url, $user;

    public function __construct(URL $url, $user = false)
    {
        $this->url = clone $url;
        $this->user = $user;
    }

    public function __toString()
    {
        ob_start();
        echo "<section class='action-menu'><h1>Action menu</h1><nav class='action-menu'>";
        // output buffer contents separately
        ob_start();
        $this->printUserActions();
        $this->printStaticActions();
        $this->printPageActions();
        // return empty if no contents
        if (!ob_get_length()) {
            ob_end_clean();
            ob_end_clean();
            return '';
        } else {
            ob_end_flush();
        }
        // close up and return
        echo "</nav></section>";
        return ob_get_clean();
    }

    protected function printUserActions()
    {
        if (!$this->user) {
            return;
        }
        $user = Users::current();
        if ($user) {
            echo "<li class='user-actions user-actions-signedin'><h2><span class='user-welcome'>Welcome </span>$user</h2><nav>";
            echo "<li class='signout-link'>" . Users::signoutUrl()->html(['signout-link']) . "</li>";
            foreach (Router::staticActions('user') as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</nav></li>";
        } elseif (Config::get('ui.action-menus.guestui')) {
            $user = Users::guest();
            echo "<li class='user-actions user-actions-guest'><h2><span class='user-welcome'>Welcome </span>$user</h2><nav>";
            echo "<li class='signin-link'>" . Users::signinUrl()->html(['signin-link']) . "</li>";
            foreach (Router::staticActions('guest') as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</nav></li>";
        }
    }

    protected function printPageActions()
    {
        if ($this->url->page() && $actions = Router::pageActions($this->url->page())) {
            echo "<li class='page-actions'><h2><span class='action-menu-sublabel'>Page </span>" . $this->url->page()->url()->html() . "</h2><nav>";
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</nav></li>";
        }
    }

    protected function printStaticActions()
    {
        if ($actions = Router::staticActions($this->url->route())) {
            if (Router::staticRouteExists($this->url->route(), 'index')) {
                $title = (new URL('/~' . $this->url->route()))->html();
            } else {
                $title = '<a>' . $this->url->route() . '</a>';
            }
            echo "<li class='static-actions'><h2><span class='action-menu-sublabel'>Section </span>$title</h2><nav>";
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</nav></li>";
        }
    }
}
