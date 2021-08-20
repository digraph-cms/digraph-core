<?php

namespace DigraphCMS\UI;

use DigraphCMS\Content\Router;
use DigraphCMS\URL\URL;

class Actionbar
{
    protected $url;

    public function __construct(URL $url)
    {
        $this->url = clone $url;
    }

    public function __toString()
    {
        ob_start();
        echo "<ul class='actionbar'>";
        $this->printActions();
        $this->printStaticActions();
        echo "</ul>";
        return ob_get_clean();
    }

    protected function printActions()
    {
        $this->printPageActions();
    }

    protected function printPageActions()
    {
        if ($this->url->page() && $actions = Router::pageActions($this->url->page())) {
            echo "<li><strong class='page-actions'>" . $this->url->page()->url()->html() . "</strong><ul>";
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</ul></li>";
        }
    }

    protected function printStaticActions()
    {
        if ($actions = Router::staticActions($this->url->route())) {
            if (Router::staticRouteExists($this->url->route(), 'index')) {
                $title = (new URL('/~' . $this->url->route()))->html();
            } else {
                $title = $this->url->route();
            }
            echo "<li><strong class='static-actions'>$title</strong><ul>";
            foreach ($actions as $url) {
                echo "<li>" . $url->html([], true) . "</li>";
            }
            echo "</ul></li>";
        }
    }
}
