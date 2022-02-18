<?php

namespace DigraphCMS\UI;

use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\MenuBar\MenuBar;
use DigraphCMS\UI\MenuBar\MenuItem;
use DigraphCMS\UI\MenuBar\MenuItemFrame;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

class UserMenu extends MenuBar
{
    protected $themeItem, $userItem, $loginItem, $logoutItem;

    public function __construct()
    {
        if ($user = Users::current()) {
            // actions for authenticated user
            $this->userItem = $this->addURL($user->profile())
                ->addClass('menuitem--user');
            $this->logoutItem = $this->addURL(Users::signoutUrl(Context::url()))
                ->addClass('menuitem--logout');
            // dispatch user event
            Dispatcher::dispatchEvent('onUserMenu_user', [$this]);
        } else {
            // actions for non-authenticated user
            $this->loginItem = $this->addURL(Users::signinUrl(Context::url()))
                ->addClass('menuitem--login');
            // dispatch guest event
            Dispatcher::dispatchEvent('onUserMenu_guest', [$this]);
        }
        // add color settings
        $this->addChild(
            $this->themeItem = (new MenuItemFrame(null, 'Theme', new URL('/~api/v1/theme-menu.php')))
                ->addClass('menuitem--theme')
        );
        // global events for adding to menu
        Dispatcher::dispatchEvent('onUserMenu', [$this]);
    }

    public function themeItem(): MenuItemFrame
    {
        return $this->themeItem;
    }

    public function userItem(): ?MenuItem
    {
        return $this->userItem;
    }

    public function loginItem(): ?MenuItem
    {
        return $this->loginItem;
    }

    public function logoutItem(): ?MenuItem
    {
        return $this->logoutItem;
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'menubar--usermenu',
                Session::user() ? 'menubar--usermenu--user' : 'menubar--usermenu--guest'
            ]
        );
    }
}
