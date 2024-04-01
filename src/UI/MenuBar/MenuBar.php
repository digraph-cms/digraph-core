<?php

namespace DigraphCMS\UI\MenuBar;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\HTML\DIV;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\URL\URL;

class MenuBar extends DIV
{
    protected $checkPermissions = false;
    /** @var callable|null */
    protected $page_filter = null;
    /** @var callable|null */
    protected $url_filter = null;

    public function __construct(
        callable|null $page_filter = null,
        callable|null $url_filter = null
    ) {
        $this->page_filter = $page_filter;
        $this->url_filter = $url_filter;
    }

    /**
     * Set whether this menu should check the permissions of its children when
     * rendering. Default is to not check them, because it takes a significant
     * amount of time.
     *
     * @param boolean $check
     * @return static
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
        $children = $page->children()->fetchAll();
        $children = array_filter(
            $children,
            $this->filterPage(...)
        );
        if ($depth-- && $children) {
            $subMenu = new MenuBar($this->page_filter, $this->url_filter);
            $item->addChild($subMenu);
            foreach ($children as $child) {
                $subMenu->addPageDropdown($child, null, $openContext, $depth);
            }
        }
        return $item;
    }

    public function addUrlDropdown(URL $url, string $label = null, bool $openContext = false, int $depth = 3): MenuItem
    {
        $item = $this->addURL($url, $label);
        if ($openContext) {
            if ($url == Context::url()) $item->addClass('menuitem--open');
            elseif (in_array($url, Breadcrumb::breadcrumb())) $item->addClass('menuitem--open');
        }
        $children = Router::staticActions($url->route(), true);
        if ($page = $url->page()) {
            $children = array_merge($children, Router::pageActions($page, true));
        }
        $children = array_filter(
            $children,
            $this->filterUrl(...)
        );
        if ($depth-- && $children) {
            $subMenu = new MenuBar($this->page_filter, $this->url_filter);
            $item->addChild($subMenu);
            foreach ($children as $child) {
                $subMenu->addUrlDropdown($child, null, $openContext, $depth);
            }
        }
        return $item;
    }

    protected function filterPage(AbstractPage $page): bool
    {
        if ($this->page_filter) return call_user_func($this->page_filter, $page);
        else return true;
    }

    protected function filterUrl(URL $url): bool
    {
        if ($this->url_filter) return call_user_func($this->url_filter, $url);
        elseif ($url->page() && !$this->filterPage($url->page())) return false;
        else return true;
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
