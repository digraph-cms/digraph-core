<?php

namespace DigraphCMS\UI;

use DigraphCMS\Config;
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
    protected $adminItem, $themeItem, $userItem, $loginItem, $logoutItem, $inboxItem;
    protected $checkPermissions = true;

    public function __construct()
    {
        if ($user = Users::current()) {
            // actions for authenticated user
            // user profile link
            $this->userItem = $this->addURL($user->profile(), $user->name())
                ->addClass('menuitem--user');
            // logout link
            $this->logoutItem = $this->addURL(Users::signoutUrl(Context::url()))
                ->addClass('menuitem--logout');
            // set menu label
            $this->setAttribute('aria-label', 'User menu');
            // dispatch user event
            Dispatcher::dispatchEvent('onUserMenu_user', [$this]);
        } else {
            // actions for non-authenticated user
            if (Config::get('usermenu.guest_signin')) {
                $this->loginItem = $this->addURL(Users::signinUrl(Context::url()))
                    ->addClass('menuitem--login');
            }
            // set menu label
            $this->setAttribute('aria-label', 'Guest menu');
            // dispatch guest event
            Dispatcher::dispatchEvent('onUserMenu_guest', [$this]);
        }
        // add admin settings
        $this->adminItem = $this->addURL(new URL('/~admin/'), 'Admin')
            ->addClass('menuitem--admin');
        // add theme settings
        $this->addChild(
            $this->themeItem =
                (new MenuItemFrame(
                    null,
                    'Theme',
                    new URL('/~api/v1/usermenu/theme.php')
                ))
                ->addClass('menuitem--theme')
        );
        // global events for adding to menu
        Dispatcher::dispatchEvent('onUserMenu', [$this]);
    }

    public function adminItem(): MenuItemFrame
    {
        return $this->adminItem;
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
