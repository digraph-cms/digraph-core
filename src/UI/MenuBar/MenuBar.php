<?php

namespace DigraphCMS\UI\MenuBar;

use DigraphCMS\Content\Page;
use DigraphCMS\HTML\DIV;
use DigraphCMS\URL\URL;

class MenuBar extends DIV
{
    public function addPage(Page $page, string $label = null): MenuItem
    {
        $item = new MenuItem($page->url(), $label ?? $page->name());
        $this->addChild($item);
        return $item;
    }

    public function addURL(URL $url, string $label = null): MenuItem
    {
        $item = new MenuItem($url, $label ?? $url->name());
        $this->addChild($item);
        return $item;
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'menubar'
            ]
        );
    }
}
