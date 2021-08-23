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
        echo "<nav class='action-menu'><h1>Action menu</h1>";
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
        echo "</nav>";
        return ob_get_clean();
    }

    protected function printUserActions()
    {
        if (!$this->user) {
            return;
        }
        $user = Users::current();
        if ($user) {
            echo "<div class='user-actions user-actions-signedin'><h2>$user</h2><nav><ul>";
            echo "<li class='signout-link'>" . Users::signoutUrl()->html(['signout-link']) . "</li>";
            foreach (Router::staticActions('user') as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</ul></nav></div>";
        } elseif (Config::get('ui.action-menu.guestui')) {
            $user = Users::guest();
            echo "<div class='user-actions user-actions-guest'><h2>$user</h2><nav><ul>";
            echo "<li class='signin-link'>" . Users::signinUrl()->html(['signin-link']) . "</li>";
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
            Router::staticActions($this->url->route()),
            function (URL $url) {
                return $url->permissions();
            }
        );
        if ($actions = Router::pageActions($this->url->page())) {
            echo "<div class='page-actions'><h2>" . $this->url->page()->url()->html() . "</h2><nav><ul>";
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</ul></nav></div>";
        }
    }

    protected function printStaticActions()
    {
        $actions = array_filter(
            Router::staticActions($this->url->route()),
            function (URL $url) {
                return $url->permissions();
            }
        );
        if ($actions) {
            if (Router::staticRouteExists($this->url->route(), 'index')) {
                $title = (new URL('/~' . $this->url->route()))->html();
            } else {
                $title = '<a>' . $this->url->route() . '</a>';
            }
            echo "<div class='static-actions'><h2>$title</h2><nav><ul>";
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</ul></nav></div>";
        }
    }
}
