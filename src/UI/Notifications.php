<?php

namespace DigraphCMS\UI;

class Notifications
{
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

    public static function print(string $message, string $type = null, string $class = '')
    {
        if ($type) {
            $class .= " $type";
        }
        echo "<div class='notification$class'>";
        echo $message;
        echo "</div>";
    }
}
