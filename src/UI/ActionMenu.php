<?php

namespace DigraphCMS\UI;

use DigraphCMS\Cache\UserCacheNamespace;
use DigraphCMS\Content\Router;
use DigraphCMS\URL\URL;

class ActionMenu
{
    protected $url, $cache;

    public function __construct(URL $url)
    {
        $this->url = clone $url;
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
                    && $url->page() == $this->url->page();
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
            echo "<div class='action-menu__page-actions'><h2>" . $this->url->page()->url()->html() . "</h2><nav><ul>";
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
            echo "<div class='action-menu__static-actions'>";
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
