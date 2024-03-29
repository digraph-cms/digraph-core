<?php

namespace DigraphCMS\UI;

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\UI\MenuBar\MenuBar;
use DigraphCMS\UI\MenuBar\MenuItem;
use DigraphCMS\URL\URL;

class ActionMenu extends MenuBar
{
    protected $url;
    protected $adderItem, $adderMenu;
    protected static $contextActions = [];
    protected static $contextHide = false;
    protected $checkPermissions = true;

    public static function hide()
    {
        static::$contextHide = true;
    }

    public static function isHidden(): bool
    {
        return static::$contextHide;
    }

    public static function addContextAction(URL $url, string $name = null)
    {
        static::$contextActions[] = [$url, $name];
    }

    public function __construct(URL $url = null)
    {
        $this->url = $url ? clone $url : Context::url();
        // set menu label and class
        $this->setAttribute('aria-label', 'Action menu');
        // context action if URL is same as context
        if (Context::response()->status() == 200 && $this->url->__toString() == Context::url()->__toString()) {
            foreach (static::$contextActions as $a) {
                $this->addUrl($a[0], @$a[1] ?? $a[0]->name(true))
                    ->addClass('menuitem--context-action')
                    ->addClass('menuitem--' . $a[0]->action());
            }
        }
        // page actions
        if ($page = $this->url->page()) {
            $this->addClass('menubar--actionmenu--page');
            $this->addClass('menubar--actionmenu--page--' . $page->class());
            // regular action links
            foreach (Router::pageActions($page, true) as $url) {
                $this->addURL($url, $url->name(true))
                    ->addClass('menuitem--page-action')
                    ->addClass('menuitem--' . $url->action());
            }
            // event hooks
            Dispatcher::dispatchEvent('onActionMenu_page', [$this, $page]);
            Dispatcher::dispatchEvent('onActionMenu_page_' . $page->class(), [$this, $page]);
        }
        // static actions
        $actions = Router::staticActions($this->url->route(), true);
        foreach ($actions as $url) {
            $this->addURL($url, $url->name(true))
                ->addClass('menuitem--static-action')
                ->addClass('menuitem--' . $url->action());
        }
        if ($actions) {
            $this->addClass('menubar--actionmenu--static');
            $this->addClass('menubar--actionmenu--static--' . $url->route());
        }
        // event hooks
        Dispatcher::dispatchEvent('onActionMenu', [$this]);
        Dispatcher::dispatchEvent('onActionMenu_' . $this->url->route(), [$this]);
        // child-adding dropdown
        if ($page = $this->url->page()) {
            $addable = array_filter(
                $page->addableTypes(),
                function (string $type) use ($page) {
                    return $page->url_add($type)->permissions();
                }
            );
            if ($addable) {
                $this->addClass('menubar--actionmenu--has-adder');
                $this->addChild(
                    $this->adderItem = (new MenuItem(null, 'Add'))
                        ->addClass('menuitem--page-action--adder')
                );
                $this->adderItem->addChild(
                    $this->adderMenu = new MenuBar
                );
                foreach ($addable as $type) {
                    $this->adderMenu->addURL(
                        $page->url_add($type),
                        $type
                    );
                }
            }
        }
    }

    public function url(): URL
    {
        return clone $this->url;
    }

    public function adderItem(): MenuItem
    {
        return $this->adderItem;
    }

    public function adderMenu(): MenuBar
    {
        return $this->adderMenu;
    }

    public function classes(): array
    {
        $classes = parent::classes();
        $classes[] = 'menubar--actionmenu';
        return $classes;
    }

    public function toString(): string
    {
        if ($this->children()) {
            return parent::toString();
        } else {
            return '';
        }
    }
}
