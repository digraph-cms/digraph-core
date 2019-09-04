<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;
use Flatrr\Config\Config;

class Strings extends AbstractHelper
{
    public function obfuscate($text)
    {
        $text = \base64_encode($text);
        return '<script>document.write(atob("'.$text.'"));</script><noscript><em>[javascript required to view]</em></noscript>';
    }

    public function allhtmlentities($str)
    {
        $str = mb_convert_encoding($str, 'UTF-32', 'UTF-8');
        $t = unpack("N*", $str);
        $t = array_map(function ($n) {
            return "&#$n;";
        }, $t);
        return implode("", $t);
    }

    public function string($name, array $args=[]) : string
    {
        $string = $this->cms->config['strings.'.$name];
        if ($string !== null) {
            foreach ($args as $key => $value) {
                $string = str_replace('!'.$key, $value, $string);
            }
            return $string;
        }
        return '[strings.'.$name.']';
    }

    public function date($time=null)
    {
        if (!$time) {
            $time = time();
        }
        return date($this->string('date.format.date'), $time);
    }

    public function dateHTML($time=null)
    {
        if (!$time) {
            $time = time();
        }
        $formatted = $this->date($time);
        return "<time datetime=\"".date('c', $time)."\" title=\"".$this->datetime($time)."\">$formatted</time>";
    }

    public function datetime($time=null)
    {
        if (!$time) {
            $time = time();
        }
        return date($this->string('date.format.datetime'), $time);
    }

    public function datetimeHTML($time=null)
    {
        if (!$time) {
            $time = time();
        }
        $formatted = $this->datetime($time);
        return "<time datetime=\"".date('c', $time)."\">$formatted</time>";
    }

    public function filesize($bytes)
    {
        return $this->unit_string(
            $bytes,
            [
                ' bytes'=>1,
                'KB'=>1024,
                'MB'=>1024,
                'GB'=>1024,
                'TB'=>1024,
                'PB'=>1024
            ]
        );
    }

    public function unit_string($size, array $names, bool $prefix=false, int $dec=1)
    {
        do {
            $base = reset($names);
            $name = key($names);
            $size /= $base;
            array_shift($names);
        } while ($size >= reset($names) && $names);
        return (round($size*pow(10, $dec))/pow(10, $dec)).$name;
    }

    public function filesizeHTML($bytes)
    {
        return "<a title='".$bytes." bytes'>".$this->filesize($bytes)."</a>";
    }
}
