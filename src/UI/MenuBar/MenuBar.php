<?php

namespace DigraphCMS\UI\MenuBar;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Context;
use DigraphCMS\HTML\DIV;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\URL\URL;

class MenuBar extends DIV
{
    protected $checkPermissions = false;

    /**
     * Set whether this menu should check the permissions of its children when
     * rendering. Default is to not check them, because it takes a significant
     * amount of time.
     *
     * @param boolean $check
     * @return $this
     */
    public function setCheckPermissions(bool $check)
    {
        $this->checkPermissions = $check;
        return $this;
    }

    public function addPage(AbstractPage $page, string $label = null): MenuItem
    {
        $item = new MenuItem($page->url(), $label ?? $page->name());
        $this->addChild($item);
        return $item;
    }

    public function addPageDropdown(AbstractPage $page, string $label = null, bool $openContext = false, int $depth = 3): MenuItem
    {
        $item = $this->addPage($page, $label);
        if ($openContext) {
            if ($page->url() == Context::url()) $item->addClass('menuitem--open');
            elseif (in_array($page->url(), Breadcrumb::breadcrumb())) $item->addClass('menuitem--open');
        }
        if ($depth-- && $page->children()->count()) {
            $subMenu = new MenuBar;
            $item->addChild($subMenu);
            foreach ($page->children() as $child) {
                $subMenu->addPageDropdown($child, null, $openContext, $depth);
            }
        }
        return $item;
    }

    public function addURL(URL $url, string $label = null): MenuItem
    {
        $item = new MenuItem($url, $label ?? $url->name());
        $this->addChild($item);
        return $item;
    }

    /**
     * Children in MenuBars are automatically filtered by their URL permissions
     * if setCheckPermissions has been set to true
     *
     * @return array
     */
    public function children(): array
    {
        // return unfiltered list if checkPermissions is off
        if (!$this->checkPermissions) return parent::children();
        // otherwise filter by URL permissions
        return array_filter(
            parent::children(),
            function ($e) {
                if ($e instanceof MenuItem) {
                    if (($url = $e->url()) instanceof URL) {
                        return $url->permissions();
                    }
                }
                return true;
            }
        );
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
