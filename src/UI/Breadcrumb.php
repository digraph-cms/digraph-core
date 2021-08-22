<?php

namespace DigraphCMS\UI;

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\URL\URL;

class Breadcrumb
{
    protected static $top;
    protected static $parents = [];

    public static function print()
    {
        $breadcrumb = static::breadcrumb();
        if (count($breadcrumb) >= Config::get('ui.breadcrumb.min_length')) {
            echo "<section class='breadcrumb'><h1>Breadcrumb</h1><nav class='breadcrumb'>";

            foreach ($breadcrumb as $url) {
                echo "<li>" . $url->html() . "</li>";
            }
            if (Config::get('ui.breadcrumb.include_current')) {
                echo "<li>" . static::top()->html(['breadcrumb-current']) . "</li>";
            }
            echo "</nav></section>";
        }
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
        return $breadcrumb;
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