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

    public function __construct(URL $url = null)
    {
        $this->url = $url ? clone $url : Context::url();
        // set menu label and class
        $this->setAttribute('aria-label', 'Action menu');
        // page actions
        if ($page = $this->url->page()) {
            $this->addClass('menubar--actionmenu--page');
            $this->addClass('menubar--actionmenu--page--' . $page->class());
            // regular action links
            foreach (Router::pageActions($page) as $url) {
                if (substr($url->action(), 0, 1) == '_') {
                    continue;
                }
                $this->addURL($url, $url->name(true))
                    ->addClass('menuitem--page-action')
                    ->addClass('menuitem--' . $url->action());
            }
            // event hooks
            Dispatcher::dispatchEvent('onActionMenu_page', [$this, $page]);
            Dispatcher::dispatchEvent('onActionMenu_page_' . $page->class(), [$this, $page]);
        }
        // static actions
        $actions = array_filter(
            Router::staticActions($this->url->route()),
            function (URL $url) {
                return
                    substr($url->action(), 0, 1) != '_'
                    && $url->route() == $url->route();
            }
        );
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
