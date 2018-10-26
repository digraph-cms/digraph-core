<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;
use Flatrr\Config\Config;

class Strings extends AbstractHelper
{
    public function string($name, array $args=[]) : string
    {
        if ($string = $this->cms->config['strings.'.$name]) {
            foreach ($args as $key => $value) {
                $string = str_replace('!'.$key, $value, $string);
            }
            return $string;
        }
        return '[strings.'.$name.']';
    }

    public function date($time)
    {
        return date($this->string('date.format.date'), $time);
    }

    public function dateHTML($time)
    {
        $formatted = $this->date($time);
        return "<time datetime=\"".date('c', $time)."\">$formatted</time>";
    }

    public function datetime($time)
    {
        return date($this->string('date.format.datetime'), $time);
    }

    public function datetimeHTML($time)
    {
        $formatted = $this->datetime($time);
        return "<time datetime=\"".date('c', $time)."\">$formatted</time>";
    }
}
