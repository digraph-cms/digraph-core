<?php

namespace DigraphCMS\UI;

use Caxy\HtmlDiff\HtmlDiff;
use Countable;
use DateTime;
use DateTimeZone;
use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\DB\AbstractMappedSelect;
use Envms\FluentPDO\Queries\Select;

Format::_init();

class Format
{
    protected static $timezone, $dateFormat, $datetimeFormat, $timeFormat, $dateFormat_thisYear, $datetimeFormat_thisYear, $datetimeFormat_today, $dateFormat_today;
    protected static $dateFormat_day_of_week, $datetimeFormat_day_of_week, $dateFormat_thisYear_day_of_week, $datetimeFormat_thisYear_day_of_week;

    public static function _init()
    {
        static::$timezone = Theme::timezone();
        static::$dateFormat = Config::get('theme.format.date') ?? 'F j, Y';
        static::$dateFormat_day_of_week = Config::get('theme.format.date_dayofweek') ?? 'l, F j, Y';
        static::$dateFormat_today = Config::get('theme.format.datetime_today') ?? '\t\o\d\a\y';
        static::$datetimeFormat = Config::get('theme.format.datetime') ?? 'F j, Y, g:ia';
        static::$datetimeFormat_day_of_week = Config::get('theme.format.datetime_dayofweek') ?? 'l, F j, Y, g:ia';
        static::$timeFormat = Config::get('theme.format.time') ?? 'g:ia';
        static::$dateFormat_thisYear = Config::get('theme.format.date_thisyear') ?? 'F j';
        static::$dateFormat_thisYear_day_of_week = Config::get('theme.format.date_thisyear') ?? 'l, F j';
        static::$datetimeFormat_thisYear = Config::get('theme.format.datetime_thisyear') ?? 'F j, g:ia';
        static::$datetimeFormat_thisYear_day_of_week = Config::get('theme.format.datetime_thisyear_dayofweek') ?? 'l, F j, g:ia';
        static::$datetimeFormat_today = Config::get('theme.format.datetime_today') ?? '\t\o\d\a\y \a\t g:ia';
    }

    /**
     * Combine an iterable of stringable items into a list, separated by commas,
     * with an oxford comma and the word "and" between the last two items
     * (unless there are only two items in which case there is no oxford comma)
     * 
     * @param iterable $source 
     * @return string 
     */
    public function list(iterable $source): string
    {
        $items = [];
        foreach ($source as $item) $items[] = $item;
        switch (count($items)) {
            case 0:
                return '{empty list}';
            case 1:
                return $items[0];
            case 2:
                return $items[0] . ' and ' . $items[1];
            default:
                $last = array_pop($items);
                return implode(', ', $items) . ', and ' . $last;
        }
    }

    /**
     * Determine whether to use an "s" to pluralize strings referring to the given item. If given a
     * string the length will be used, if given a numeric value it will be used directly, otherwise
     * an appropriate counting method will be used.
     * 
     * @param array|string|Countable|int|float|AbstractMappedSelect|Select $items 
     * @return string 
     */
    public static function s(array|string|Countable|int|float|AbstractMappedSelect|Select $items): string
    {
        if ($items instanceof AbstractMappedSelect || $items instanceof Select) {
            $count = $items->count();
        } elseif (is_array($items) || is_countable($items)) {
            $count = count($items);
        } elseif (is_string($items)) {
            $count = strlen($items);
        } elseif (is_numeric($items)) {
            $count = $items;
        }
        if ($count == 1) return '';
        else return 's';
    }

    /**
     * adapted from urodoz/truncateHTML.
     *
     * (c) Albert Lacarta <urodoz@gmail.com>
     *
     *
     * @param string $text
     * @param integer $length
     * @param string $ending
     * @param boolean $exact
     * @return string
     */
    public static function truncateHTML(
        $text,
        $length = 100,
        $ending = '...',
        $exact = false
    ) {
        // if the plain text is shorter than the maximum length, return the whole text
        if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
            return $text;
        }
        // splits all html-tags to scannable lines
        preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
        $total_length = strlen($ending);
        $open_tags = array();
        $truncate = '';
        foreach ($lines as $line_matchings) {
            // if there is any html-tag in this line, handle it and add it (uncounted) to the output
            if (!empty($line_matchings[1])) {
                // if it's an "empty element" with or without xhtml-conform closing slash
                if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                    // do nothing
                    // if tag is a closing tag
                } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                    // delete tag from $open_tags list
                    $pos = array_search($tag_matchings[1], $open_tags);
                    if ($pos !== false) {
                        unset($open_tags[$pos]);
                    }
                    // if tag is an opening tag
                } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                    // add tag to the beginning of $open_tags list
                    array_unshift($open_tags, strtolower($tag_matchings[1]));
                }
                // add html-tag to $truncate'd text
                $truncate .= $line_matchings[1];
            }
            // calculate the length of the plain text part of the line; handle entities as one character
            $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
            if ($total_length + $content_length > $length) {
                // the number of characters which are left
                $left = $length - $total_length;
                $entities_length = 0;
                // search for html entities
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                    // calculate the real length of all entities in the legal range
                    foreach ($entities[0] as $entity) {
                        if ($entity[1] + 1 - $entities_length <= $left) {
                            $left--;
                            $entities_length += strlen($entity[0]);
                        } else {
                            // no more characters left
                            break;
                        }
                    }
                }
                $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                // maximum lenght is reached, so get off the loop
                break;
            } else {
                $truncate .= $line_matchings[2];
                $total_length += $content_length;
            }
            // if the maximum length is reached, get off the loop
            if ($total_length >= $length) {
                break;
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if ($spacepos !== false) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // close all unclosed html-tags
        foreach ($open_tags as $tag) {
            $truncate .= '</' . $tag . '>';
        }
        // add the defined ending to the text
        $truncate .= $ending;
        return $truncate;
    }

    public static function htmlDiff(string $a, string $b): string
    {
        return Cache::get(
            'format/htmldiff/' . md5(md5($a) . md5($b)),
            function () use ($a, $b) {
                return (new HtmlDiff($a, $b))->build();
            },
            -1
        );
    }

    public static function base64obfuscate(string $string, string $message = 'javascript required to view')
    {
        return sprintf(
            '<span class="obfuscated obfuscated--base64"><span class="obfuscated__data">%s</span><span class="obfuscated__message">%s</span></span>',
            base64_encode($string),
            $message
        );
    }

    public static function filesize(int $bytes, int $decimals = 1): string
    {
        static $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        if ($bytes == 0) return '0B';
        $factor = floor(log($bytes, 1024));
        return preg_replace("/\.([0-9]*)0+$/", "$1", sprintf("%.{$decimals}f", $bytes / pow(1024, $factor))) . @$size[$factor];
    }

    public static function date($date, $textOnly = false, $precise = false, $day_of_week = false): string
    {
        $date = static::parseDate($date);
        if (!$precise && $date->format('Y') == date('Y')) {
            if ($date->format('Ydm') == date('Ydm')) {
                $text = $date->format(static::$dateFormat_today);;
            } elseif ($day_of_week) {
                $text = $date->format(static::$dateFormat_thisYear_day_of_week);
            } else {
                $text = $date->format(static::$dateFormat_thisYear);
            }
        } elseif ($day_of_week) {
            $text = $date->format(static::$dateFormat_day_of_week);
        } else {
            $text = $date->format(static::$dateFormat);
        }
        if (!$textOnly) {
            $text = static::wrapDateHTML($date, $text);
        }
        return $text;
    }

    public static function datetime($date, $textOnly = false, $precise = false, $day_of_week = false): string
    {
        $date = static::parseDate($date);
        if (!$precise && $date->format('Y') == date('Y')) {
            if ($date->format('Ydm') == date('Ydm')) {
                $text = $date->format(static::$datetimeFormat_today);
            } elseif ($day_of_week) {
                $text = $date->format(static::$datetimeFormat_thisYear_day_of_week);
            } else {
                $text = $date->format(static::$datetimeFormat_thisYear);
            }
        } elseif ($day_of_week) {
            $text = $date->format(static::$datetimeFormat_day_of_week);
        } else {
            $text = $date->format(static::$datetimeFormat);
        }
        if (!$textOnly) {
            $text = static::wrapDateHTML($date, $text);
        }
        return $text;
    }

    public static function time($date, $textOnly = false): string
    {
        $date = static::parseDate($date);
        $text = $date->format(static::$timeFormat);
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
        } else {
            throw new \Exception("Error parsing date");
        }
    }

    public static function timezone(): DateTimeZone
    {
        return static::$timezone;
    }

    public static function js_encode_object($input): string
    {
        if (is_array($input)) {
            $arr = [];
            foreach ($input as $k => $v) {
                $v = static::js_encode_object($v);
                $arr[] = "$k:$v";
            }
            return "{" . implode(',', $arr) . "}";
        } elseif (is_string($input)) {
            $input = preg_replace("/[\\\"]/", "\\$0", $input);
            return "\"$input\"";
        } elseif (is_numeric($input)) {
            return strval($input);
        } elseif (is_object($input)) {
            if (method_exists($input, '__toString')) {
                return static::js_encode_object($input->__toString());
            } else {
                throw new \Exception("Can only object encode objects with __toString method");
            }
        } elseif (is_null($input)) {
            return 'null';
        } elseif (!$input) {
            return 'false';
        } else {
            return 'true';
        }
    }
}