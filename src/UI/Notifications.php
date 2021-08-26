<?php

namespace DigraphCMS\UI;

use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Cookies;

Dispatcher::addSubscriber(Notifications::class);

class Notifications
{
    protected static $flashes = [];
    protected static $notifications = [];

    public static function printSection()
    {
        // pull flash notifications
        if ($flashes = Cookies::get('system', 'flashnotifications')) {
            Context::response()->private(true);
            foreach ($flashes as list($message, $type, $class)) {
                static::add($message, "$type flash-notification", $class);
            }
            Cookies::unset('system', 'flashnotifications');
        }
        // display notifications
        if (static::$notifications) {
            echo "<section id='notifications'><h1>Notifications</h1>";
            foreach (static::$notifications as list($message, $type, $class)) {
                static::print($message, $type, $class);
            }
            echo "</section>";
        }
    }

    public static function notice(string $message, string $class = '')
    {
        static::add($message, 'notice', $class);
    }

    public static function warning(string $message, string $class = '')
    {
        static::add($message, 'warning', $class);
    }

    public static function error(string $message, string $class = '')
    {
        static::add($message, 'error', $class);
    }

    public static function confirmation(string $message, string $class = '')
    {
        static::add($message, 'confirmation', $class);
    }

    public static function add(string $message, string $type = 'unspecified', string $class = '')
    {
        static::$notifications[] = [$message, $type, $class];
    }

    public static function flashNotice(string $message, string $class = '')
    {
        static::flash($message, 'notice', $class);
    }

    public static function flashWarning(string $message, string $class = '')
    {
        static::flash($message, 'warning', $class);
    }

    public static function flashError(string $message, string $class = '')
    {
        static::flash($message, 'error', $class);
    }

    public static function flashConfirmation(string $message, string $class = '')
    {
        static::flash($message, 'confirmation', $class);
    }

    public static function flash(string $message, string $type = 'unspecified', string $class = '')
    {
        Cookies::require('system');
        static::$flashes[] = [$message, $type, $class];
    }

    public static function onBeforeShutdown()
    {
        if (static::$flashes) {
            $flashes = Cookies::get('system', 'flashnotifications') ?? [];
            $flashes = array_merge($flashes, static::$flashes);
            Cookies::set('system', 'flashnotifications', $flashes);
        }
    }

    public static function printNotice(string $message, string $class = '')
    {
        static::print($message, 'notice', $class);
    }

    public static function printWarning(string $message, string $class = '')
    {
        static::print($message, 'warning', $class);
    }

    public static function printError(string $message, string $class = '')
    {
        static::print($message, 'error', $class);
    }

    public static function printConfirmation(string $message, string $class = '')
    {
        static::print($message, 'confirmation', $class);
    }

    public static function print(string $message, string $type = 'unspecified', string $class = '')
    {
        if ($type) {
            $class .= " $type";
        }
        echo "<div class='notification$class'>";
        echo $message;
        echo "</div>";
    }
}
