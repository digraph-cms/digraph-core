<?php

namespace DigraphCMS\UI;

use DateTime;
use DateTimeZone;
use DigraphCMS\Config;

Format::_init();

class Format
{
    protected static $timezone, $dateFormat, $datetimeFormat;

    public static function _init()
    {
        static::$timezone = new DateTimeZone(Config::get('theme.timezone') ?? 'UTC');
        static::$dateFormat = Config::get('theme.format.date') ?? 'F j, Y';
        static::$datetimeFormat = Config::get('theme.format.datetime') ?? 'F j, Y, g:i a';
    }

    public static function filesize(int $bytes, int $decimals = 1): string
    {
        static $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    public static function date($date, $textOnly = false): string
    {
        $date = static::parseDate($date);
        $text = $date->format(static::$dateFormat);
        if (!$textOnly) {
            $text = static::wrapDateHTML($date, $text);
        }
        return $text;
    }

    public static function datetime($date, $textOnly = false): string
    {
        $date = static::parseDate($date);
        $text = $date->format(static::$datetimeFormat);
        if (!$textOnly) {
            $text = static::wrapDateHTML($date, $text);
        }
        return $text;
    }

    protected static function wrapDateHTML(DateTime $dt, string $text): string
    {
        return '<time datetime="' . $dt->format('c') . '" title="' . $dt->format(static::$datetimeFormat) . '">' . $text . '</time>';
    }

    public static function parseDate($date): DateTime
    {
        if ($date instanceof DateTime) {
            $date->setTimezone(static::timezone());
            return $date;
        } elseif (is_int($date) || preg_match('/^[0-9]+$/', $date)) {
            $dt = new DateTime('now', static::timezone());
            $dt->setTimestamp(intval($date));
            return $dt;
        } elseif (is_string($date)) {
            return new DateTime($date, static::timezone());
        }
    }

    public static function timezone(): DateTimeZone
    {
        return static::$timezone;
    }
}
