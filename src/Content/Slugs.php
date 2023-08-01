<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use URLify;

class Slugs
{
    const SLUG_CHARS = 'a-zA-Z0-9\\-_';

    public static function collisions(string $slug): bool
    {
        return Router::staticRouteExists($slug, 'index') ||
            Pages::countAll($slug) > 1;
    }

    /**
     * Determine whether a given string is a valid slug. Needs to be a valid
     * directory without leading or trailing slashes, with no crazy characters
     * in it.
     *
     * @param string $slug
     * @return boolean
     */
    public static function validate(string $slug): bool
    {
        return preg_match("@^[" . static::SLUG_CHARS . "]*(/[" . static::SLUG_CHARS . "]+)*$@", $slug);
    }

    /**
     * Retrieve all the slugs for a given page UUID
     *
     * @param string $uuid
     * @return array
     */
    public static function list(string $uuid): array
    {
        return array_map(
            function ($e) {
                return $e['url'];
            },
            DB::query()
                ->from('page_slug')
                ->where('page_uuid = ?', [$uuid])
                ->orderBy('id DESC')
                ->fetchAll()
        );
    }

    /**
     * Determine whether a slug already exists, also searches by UUID because
     * those are kind of implicitly slugs
     *
     * @param string $slug
     * @param string|null $not a UUID to skip
     * @return boolean
     */
    public static function exists(string $slug, string $not = null): bool
    {
        if ($not) {
            return !!DB::query()->from('page_slug')
                ->where('url = ? AND page_uuid <> ?', [$slug, $not])
                ->count();
        } else {
            return !!DB::query()->from('page_slug')
                ->where('url = ?', [$slug])
                ->count();
        }
    }

    public static function delete(string $page_uuid, string $slug)
    {
        DB::query()->delete('page_slug')
            ->where('page_uuid = ? AND url = ?', [$page_uuid, $slug])
            ->execute();
    }

    public static function setFromPattern(AbstractPage $page, string $pattern, bool $unique = false)
    {
        $slug = static::compilePattern($page, $pattern);
        // set slug
        static::set($page, $slug, $unique);
    }

    public static function validatePattern(AbstractPage $page, string $pattern): bool
    {
        return !!static::compilePattern($page, $pattern);
    }

    public static function compilePattern(AbstractPage $page, string $pattern): ?string
    {
        // pull variables
        $slug = preg_replace_callback(
            '/\[([a-z\-]+?)\]/',
            function (array $m) use ($page) {
                $value =
                    Dispatcher::firstValue('onSlugVariable', [$page, $m[1]]) ??
                    Dispatcher::firstValue('onSlugVariable_' . $m[1], [$page]) ??
                    $page->slugVariable($m[1]);
                return $value;
            },
            $pattern
        );
        // if slug doesn't have a value, return and UUID or existing slug will continue to be used
        if (!$slug) {
            return null;
        }
        // early cleanup
        $slug = str_replace(
            ['s\'s', '\'s', '\' '],
            ['s', 's', ' '],
            $slug
        );
        // prepend parent slug if necessary
        if (substr($slug, 0, 1) != '/' && $page->parent()) {
            $slug = $page->parent()->route() . '/' . $slug;
        }
        // run through URLify
        $slug = URLify::transliterate($slug);
        // trim and clean up
        $slug = strtolower($slug);
        $slug = preg_replace('@[^' . static::SLUG_CHARS . '\/]+@', '_', $slug);
        $slug = preg_replace('@/+@', '/', $slug);
        $slug = preg_replace('@^home/@', '', $slug);
        $slug = trim($slug, '/');
        // return
        return $slug;
    }

    public static function set(AbstractPage $page, string $slug, $unique = null)
    {
        // pull unique default from page class
        $unique = $unique ?? $page::DEFAULT_UNIQUE_SLUG;
        // validate
        if (!static::validate($slug)) {
            throw new \Exception("Slug $slug is not valid");
        }
        if ($unique) {
            // if $unique is requested slug will be renamed if it collides
            // with an existing slug or UUID
            if (static::exists($slug, $page->uuid()) || Pages::exists($slug)) {
                $slug = static::uniqueSlug($slug, $page);
            }
        } else {
            // otherwise slug must still *never* collide with a UUID
            // UUIDs *must* be usable for unique/canonical URLs
            if (Pages::exists($slug)) {
                $slug = static::uniqueSlug($slug, $page);
            }
        }
        // insert slug into database
        static::insert($page->uuid(), $slug);
    }

    protected static function uniqueSlug(string $slug, AbstractPage $page): string
    {
        $uuid = str_split(str_replace('/[^a-z0-9]/', '', substr($page->uuid(), 4)), 4);
        $slug .= '_' . substr($page->uuid(), 0, 4);
        while (static::exists($slug, $page->uuid()) || Pages::exists($slug)) {
            $slug .= array_shift($uuid);
        }
        return $slug;
    }

    protected static function insert(string $page_uuid, string $slug)
    {
        if (!static::validate($slug)) {
            throw new \Exception("Invalid slug");
        }
        static::delete($page_uuid, $slug);
        DB::query()
            ->insertInto(
                'page_slug',
                [
                    'url' => $slug,
                    'page_uuid' => $page_uuid
                ]
            )
            ->execute();
    }
}