<?php

namespace DigraphCMS\UI\Sidebar;

use DigraphCMS\Events\Dispatcher;
use DigraphCMS\UI\Templates;

class Sidebar
{
    protected static $blocks_top = [];
    protected static $blocks_middle = [];
    protected static $blocks_bottom = [];
    protected static $active = true;

    public static function add($block)
    {
        static::$blocks_middle[] = $block;
    }

    public static function addTop($block)
    {
        static::$blocks_top[] = $block;
    }

    public static function addBottom($block)
    {
        static::$blocks_bottom[] = $block;
    }

    public static function setActive(bool $active)
    {
        static::$active = $active;
    }

    public function active(): bool
    {
        return static::$active;
    }

    public static function render(): string
    {
        // skip if not active
        if (!static::$active) return '';
        // load top blocks from dispatcher
        $blocks = [];
        Dispatcher::dispatchEvent('onSidebar_first', [new SidebarEvent($blocks)]);
        // merge in internal top blocks
        $blocks = array_merge($blocks, static::$blocks_top);
        // load sidebar/top.php template to top
        if (Templates::exists('sidebar/top.php')) {
            $blocks[] = Templates::render('sidebar/top.php');
        }
        // top blocks from dispatcher
        Dispatcher::dispatchEvent('onSidebar_top', [new SidebarEvent($blocks)]);
        // merge in internal middle blocks
        $blocks = array_merge($blocks, static::$blocks_middle);
        // bottom blocks from dispatcher
        Dispatcher::dispatchEvent('onSidebar_bottom', [new SidebarEvent($blocks)]);
        // merge in internal bottom blocks
        $blocks = array_merge($blocks, static::$blocks_bottom);
        // load sidebar/bottom.php template to bottom
        if (Templates::exists('sidebar/bottom.php')) {
            $blocks[] = Templates::render('sidebar/bottom.php');
        }
        // last blocks from dispatcher
        Dispatcher::dispatchEvent('onSidebar_last', [new SidebarEvent($blocks)]);
        // execute any callable blocks
        $blocks = array_map(
            function ($block) {
                if (is_callable($block)) $block = call_user_func($block);
                return trim($block);
            },
            $blocks
        );
        // trim and filter blocks array
        $blocks = array_filter(
            $blocks,
            function ($e) {
                return !!$e;
            }
        );
        // return empty if there are no blocks left
        if (!$blocks) return '';
        // if there are blocks, return them wrapped in a DIV and imploded
        return sprintf(
            '<div id="sidebar">%s</div>',
            implode(PHP_EOL, array_map(
                function ($block) {
                    return "<div class='sidebar__block'>$block</div>";
                },
                $blocks
            ))
        );
    }
}
