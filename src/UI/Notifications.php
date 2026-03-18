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

    public static function onResponseRender(): void
    {
        if (static::$flashes) {
            $flashes = Cookies::get('ui', 'flashnotifications') ?? [];
            $flashes = array_merge($flashes, static::$flashes);
            Cookies::set('ui', 'flashnotifications', $flashes);
        }
    }

    public static function printSection()
    {
        // pull flash notifications
        if ($flashes = Cookies::get('ui', 'flashnotifications')) {
            Context::response()->private(true);
            foreach ($flashes as list($message, $type, $class)) {
                static::add($message, "$type flash-notification", $class);
            }
            Cookies::unset('ui', 'flashnotifications');
        }
        // display notifications
        echo "<section id='notifications'>";
        $notifications = static::$notifications;
        Dispatcher::dispatchEvent('onPrintNotifications', [&$notifications]);
        foreach ($notifications as list($message, $type, $class)) {
            static::printHTML($message, $type, $class);
        }
        echo "</section>";
    }

    public static function notice(string $message, string $class = ''): void
    {
        static::add($message, 'notice', $class);
    }

    public static function noticeHTML(string $message, string $class = ''): void
    {
        static::addHTML($message, 'notice', $class);
    }

    public static function warning(string $message, string $class = ''): void
    {
        static::add($message, 'warning', $class);
    }

    public static function warningHTML(string $message, string $class = ''): void
    {
        static::addHTML($message, 'warning', $class);
    }

    public static function error(string $message, string $class = ''): void
    {
        static::add($message, 'error', $class);
    }

    public static function errorHTML(string $message, string $class = ''): void
    {
        static::addHTML($message, 'error', $class);
    }

    public static function confirmation(string $message, string $class = ''): void
    {
        static::add($message, 'confirmation', $class);
    }

    public static function confirmationHTML(string $message, string $class = ''): void
    {
        static::addHTML($message, 'confirmation', $class);
    }

    public static function neutral(string $message, string $class = ''): void
    {
        static::add($message, 'neutral', $class);
    }

    public static function neutralHTML(string $message, string $class = ''): void
    {
        static::addHTML($message, 'neutral', $class);
    }

    public static function add(string $message, string $type = 'neutral', string $class = ''): void
    {
        $message = htmlspecialchars($message, ENT_QUOTES);
        static::$notifications[] = [$message, $type, $class];
    }

    public static function addHTML(string $message, string $type = 'neutral', string $class = ''): void
    {
        static::$notifications[] = [$message, $type, $class];
    }

    public static function flashNotice(string $message, string $class = ''): void
    {
        static::flash($message, 'notice', $class);
    }

    public static function flashNoticeHTML(string $message, string $class = ''): void
    {
        static::flashHTML($message, 'notice', $class);
    }

    public static function flashWarning(string $message, string $class = ''): void
    {
        static::flash($message, 'warning', $class);
    }

    public static function flashWarningHTML(string $message, string $class = ''): void
    {
        static::flashHTML($message, 'warning', $class);
    }

    public static function flashError(string $message, string $class = ''): void
    {
        static::flash($message, 'error', $class);
    }

    public static function flashErrorHTML(string $message, string $class = ''): void
    {
        static::flashHTML($message, 'error', $class);
    }

    public static function flashConfirmation(string $message, string $class = ''): void
    {
        static::flash($message, 'confirmation', $class);
    }

    public static function flashConfirmationHTML(string $message, string $class = ''): void
    {
        static::flashHTML($message, 'confirmation', $class);
    }

    public static function flashNeutral(string $message, string $class = ''): void
    {
        static::flash($message, 'neutral', $class);
    }

    public static function flashNeutralHTML(string $message, string $class = ''): void
    {
        static::flashHTML($message, 'neutral', $class);
    }

    public static function flash(string $message, string $type = 'neutral', string $class = ''): void
    {
        Cookies::required('system');
        $message = htmlspecialchars($message, ENT_QUOTES);
        static::$flashes[] = [$message, $type, $class];
    }

    public static function flashHTML(string $message, string $type = 'neutral', string $class = ''): void
    {
        Cookies::required('system');
        static::$flashes[] = [$message, $type, $class];
    }

    public static function printNotice(string $message, string $class = ''): void
    {
        static::print($message, 'notice', $class);
    }

    public static function printNoticeHTML(string $message, string $class = ''): void
    {
        static::printHTML($message, 'notice', $class);
    }

    public static function printWarning(string $message, string $class = ''): void
    {
        static::print($message, 'warning', $class);
    }

    public static function printWarningHTML(string $message, string $class = ''): void
    {
        static::printHTML($message, 'warning', $class);
    }

    public static function printError(string $message, string $class = ''): void
    {
        static::print($message, 'error', $class);
    }

    public static function printErrorHTML(string $message, string $class = ''): void
    {
        static::printHTML($message, 'error', $class);
    }

    public static function printConfirmation(string $message, string $class = ''): void
    {
        static::print($message, 'confirmation', $class);
    }

    public static function printConfirmationHTML(string $message, string $class = ''): void
    {
        static::printHTML($message, 'confirmation', $class);
    }

    public static function printNeutral(string $message, string $class = ''): void
    {
        static::print($message, 'neutral', $class);
    }

    public static function printNeutralHTML(string $message, string $class = ''): void
    {
        static::printHTML($message, 'neutral', $class);
    }

    public static function print(string $message, string $type = 'neutral', string $class = ''): void
    {
        $message = htmlspecialchars($message, ENT_QUOTES);
        if ($type) {
            $class .= " notification--$type";
        }
        if (!str_starts_with($class, ' ')) {
            $class = " $class";
        }
        echo "<div class='notification$class'>";
        echo $message;
        echo "</div>";
    }

    public static function printHTML(string $message, string $type = 'neutral', string $class = ''): void
    {
        if ($type) {
            $class .= " notification--$type";
        }
        if (!str_starts_with($class, ' ')) {
            $class = " $class";
        }
        echo "<div class='notification$class'>";
        echo $message;
        echo "</div>";
    }

}
