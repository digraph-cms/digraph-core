<?php

namespace DigraphCMS\UI;

use DigraphCMS\Cache\UserCacheNamespace;
use DigraphCMS\Config;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\URL\URL;

class Breadcrumb
{
    protected static $top;
    protected static $parents = [];

    public static function print()
    {
        $cache = new UserCacheNamespace('breadcrumb');
        echo $cache->get(
            md5(Context::url()),
            function () {
                ob_start();
                $breadcrumb = static::breadcrumb();
                if (count($breadcrumb) >= Config::get('ui.breadcrumb.min_length')) {
                    echo "<section class='breadcrumb'><h1>Breadcrumb</h1><ul>";
                    foreach ($breadcrumb as $url) {
                        echo "<li>" . $url->html([], true) . "</li>";
                    }
                    if (Config::get('ui.breadcrumb.include_current')) {
                        echo "<li class='breadcrumb-current'>" . static::top()->html(['breadcrumb-current'], true) . "</li>";
                    }
                    echo "</ul></section>";
                }
                return ob_get_clean();
            }
        );
    }

    /**
     * Get or set/override the topmost URL to use for generating the breadcrumb.
     *
     * @param URL $url
     * @return URL
     */
    public static function top(URL $url = null): URL
    {
        if ($url) {
            static::$top = $url;
        }
        return clone (static::$top ?? Context::url());
    }

    /**
     * Get or set/override the parent URL to use for generating the breadcrumb.
     *
     * @param URL $url
     * @return URL|null
     */
    public static function parent(URL $url = null): ?URL
    {
        if ($url) {
            static::$parents = [$url];
        }
        if (static::$parents) {
            return end(static::$parents);
        } else {
            return static::top()->parent();
        }
    }

    public static function pushParent(URL $url = null)
    {
        static::$parents[] = $url;
    }

    /**
     * Get/set the configured parents to be used above top() for generating the
     * breadcrumb, in order of furthest->closest.
     *
     * @param URL[] $urls
     * @return URL[]
     */
    public static function parents(array $urls = null): array
    {
        if ($urls !== null) {
            static::$parents = $urls;
        }
        if (static::$parents) {
            return static::$parents;
        } elseif ($parent = static::parent()) {
            return [$parent];
        } else {
            return [];
        }
    }

    /**
     * Undocumented function
     *
     * @return URL[]
     */
    protected static function breadcrumb(): array
    {
        $breadcrumb = static::parents();
        static::helper($breadcrumb);
        // check if [0] and [1] are home and the home page's UUID url
        if (@$breadcrumb[0] && @$breadcrumb[1] && Pages::get('home')) {
            if ($breadcrumb[0] . '' == new URL('/') . '') {
                if ($breadcrumb[1] . '' == Pages::get('home')->url(null, null, true) . '') {
                    unset($breadcrumb[1]);
                }
            }
        }
        return array_filter(
            $breadcrumb,
            function (URL $url) {
                return $url->permissions();
            }
        );
    }

    /**
     * Undocumented function
     *
     * @param URL[] $breadcrumb
     * @return void
     */
    protected static function helper(array &$breadcrumb)
    {
        if ($breadcrumb) {
            $parent = reset($breadcrumb)->parent();
            if ($parent !== null && $parent->path() != '/home/' && !in_array($parent, $breadcrumb)) {
                array_unshift($breadcrumb, $parent);
                static::Helper($breadcrumb);
            }
        }
    }
}
