<?php

namespace DigraphCMS\Content;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\Session\Session;
use Envms\FluentPDO\Queries\Select;

class Pages
{
    const SLUG_CHARS = 'a-zA-Z0-9\\-_';
    protected static $cache = [];

    /**
     * Retrieve all the alternate slugs for a given page UUID
     *
     * @param string $uuid
     * @return array
     */
    public static function alternateSlugs(string $uuid): array
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
     * Determine whether a given string is a valid slug. Needs to be a valid
     * directory without leading or trailing slashes, with no crazy characters
     * in it.
     *
     * @param string $slug
     * @return boolean
     */
    public static function validateSlug(string $slug): bool
    {
        return preg_match("@^[" . static::SLUG_CHARS . "]*(/[" . static::SLUG_CHARS . "]+)*$@", $slug);
    }

    /**
     * Quickly determine whether a given UUID exists. Does not check slugs,
     * primary or alternate.
     *
     * @param string $uuid
     * @return boolean
     */
    public static function exists(string $uuid): bool
    {
        $query = DB::query()->from('page')
            ->where('uuid = ?', [$uuid]);
        return !!$query->count();
    }

    /**
     * Generate a PageSelect object for building queries to the pages table
     *
     * @return PageSelect
     */
    public static function select(): PageSelect
    {
        return new PageSelect(
            DB::query()->from('page')
        );
    }

    /**
     * Get the child Pages of a given Page uuid
     *
     * @param string $start
     * @param string $order
     * @return PageSelect
     */
    public static function children(string $start, string $order = 'created ASC'): PageSelect
    {
        $query = static::select();
        $query->leftJoin('page_link ON uuid = end_page');
        $query->where('start_page = ?', [$start]);
        $query->order($order);
        return $query;
    }

    /**
     * Get just the uuids of the children of a given Page uuid. This is 
     * potentially quite a bit faster than children()
     *
     * @param string $start
     * @param string $order
     * @return Select
     */
    public static function childIDs(string $start, string $order = 'created ASC'): Select
    {
        $query = DB::query()->from('page_link');
        $query->where('start_page = ?', [$start]);
        $query->order($order);
        return $query->execute()->fetchColumn('end_page');
    }

    /**
     * Insert a link of the specified type between $start and $end. If no type
     * is specified the type 'normal' will be used.
     *
     * @param string $start
     * @param string $end
     * @param string $type
     * @return void
     */
    public static function insertLink(string $start, string $end, string $type = null)
    {
        return DB::query()->insertInto(
            'page_link',
            [
                'start_page' => $start,
                'end_page' => $end,
                'type' => $type ?? 'normal'
            ]
        )->execute();
    }

    /**
     * Delete link(s) matching the given criteria. If a type is specified only
     * the link of that type will be removed, otherwise all links between $start
     * and $end will be deleted.
     *
     * @param string $start
     * @param string $end
     * @param string $type
     * @return void
     */
    public static function deleteLink(string $start, string $end, string $type = null)
    {
        $query = DB::query()->deleteFrom('page_link');
        $query->where('start_page = ? AND end_page = ?', [$start, $end]);
        if ($type) {
            $query->where('type = ?', [$type]);
        }
        return $query->execute();
    }

    /**
     * Determine whether a slug already exists (also searches UUIDs, because
     * they are implicitly slugs)
     *
     * @param string $uuid_or_slug
     * @return boolean
     */
    public static function slugExists(string $uuid_or_slug): bool
    {
        $query = DB::query()->from('page')
            ->where('page_uuid = :q OR page_slug = :q', [':q' => $uuid_or_slug]);
        return !!$query->count();
    }

    /**
     * Get all pages that match the given slug/UUID, including those indicated
     * by a slug. Significantly slower than get(). A UUID match will be first,
     * followed by primary slug matches, followed by alternate slug matches.
     * Within the groups pages are sorted oldest creation date first.
     *
     * @param string $uuid_or_slug
     * @return array
     */
    public static function getAll(string $uuid_or_slug): array
    {
        // get best-case scenarios by uuid or primary slug
        $main = static::select()
            ->where('uuid = :q OR slug = :q', [':q' => $uuid_or_slug])
            ->order('CASE WHEN :q = uuid THEN 0 ELSE 1 END ASC, created ASC')
            ->fetchAll();
        // search in alternate slugs as well
        $alts = DB::query()->from('page_slug')
            ->select('page.*')
            ->leftJoin('page ON page_uuid = page.uuid')
            ->where('url = ?', [$uuid_or_slug])
            ->order('page.created ASC')
            ->fetchAll();
        $alts = array_map(static::class . '::resultToPage', $alts);
        // return results
        return array_merge($main, $alts);
    }

    /**
     * Does the same basic operation as getAll(), but only counts the results,
     * so it can be used to much more quickly determine whether and the number
     * of results available for a given UUID or slug.
     *
     * @param string $uuid_or_slug
     * @return integer
     */
    public static function countAll(string $uuid_or_slug): int
    {
        // get best-case scenarios by uuid or primary slug
        $main = static::select()
            ->where('uuid = :q OR slug = :q', [':q' => $uuid_or_slug])
            ->order('CASE WHEN uuid = :q THEN 0 ELSE 1 END ASC, created ASC')
            ->count();
        // search in alternate slugs as well
        $alts = DB::query()->from('page_slug')
            ->where('url = ?', [$uuid_or_slug])
            ->count();
        // return results
        return $main + $alts;
    }

    /**
     * Get the top result for a given slug/UUID. Will be fastest for UUID and
     * primary slug matches, but will run an additional query and search
     * alternate slugs if UUID or primary slug matches are not found. A UUID 
     * match will take precedence, followed by the oldest creation date primary 
     * slug match, followed by the oldest alternate slug match.
     *
     * @param string $uuid_or_slug
     * @return Page|null
     */
    public static function get(string $uuid_or_slug): ?Page
    {
        if (!isset(static::$cache[$uuid_or_slug])) {
            static::$cache[$uuid_or_slug] =
                self::doGet($uuid_or_slug) ??
                self::doGetByAlternateSlug($uuid_or_slug);
        }
        return static::$cache[$uuid_or_slug];
    }

    protected static function doGet(string $uuid_or_slug): ?Page
    {
        $result = DB::query()->from('page')
            ->where('uuid = :q OR slug = :q', [':q' => $uuid_or_slug])
            ->order('CASE WHEN uuid = :q THEN 0 ELSE 1 END ASC, created ASC')
            ->limit(1)
            ->execute();
        if ($result && $result = $result->fetch()) {
            return static::resultToPage($result);
        } else {
            return null;
        }
    }

    protected static function doGetByAlternateSlug(string $slug): ?Page
    {
        $result = DB::query()->from('page_slug')
            ->select('page.*')
            ->leftJoin('page ON page_uuid = page.uuid')
            ->where('url = ?', [$slug])
            ->order('page.created ASC')
            ->limit(1)
            ->execute();
        if ($result && $result = $result->fetch()) {
            return static::resultToPage($result);
        } else {
            return null;
        }
    }

    public static function objectClass(array $result): string
    {
        return Page::class;
    }

    public static function insertSlug(string $page_uuid, string $slug)
    {
        if (!static::validateSlug($slug)) {
            throw new \Exception("Invalid slug");
        }
        $check = DB::query()
            ->from('page_slug')
            ->where('url = ? AND uuid = ?', [$slug, $page_uuid]);
        if (!$check->count()) {
            DB::query()->insertInto(
                'page_slug',
                [
                    'url' => $slug,
                    'page_uuid' => $page_uuid
                ]
            );
        }
    }

    public static function update(Page $page)
    {
        DB::beginTransaction();
        // insert old slug into slugs if slug is updated
        if ($page->previousSlug()) {
            static::insertSlug($page->uuid(), $page->previousSlug());
        }
        // update values
        DB::query()
            ->update('page')
            ->where(
                'uuid = ? AND updated = ?',
                [
                    $page->uuid(),
                    $page->updatedLast()->format("Y-m-d H:i:s")
                ]
            )
            ->set([
                'slug' => $page->slug(),
                'name' => $page->name(),
                'data' => json_encode($page->get()),
                'class' => $page->class(),
                'updated_by' => Session::user()
            ])
            ->execute();
        DB::commit();
    }

    public static function insert(Page $page)
    {
        // insert value
        DB::query()
            ->insertInto(
                'page',
                [
                    'uuid' => $page->uuid(),
                    'slug' => $page->slug(),
                    'name' => $page->name(),
                    'data' => json_encode($page->get()),
                    'class' => $page->class(),
                    'created_by' => $page->createdByUUID(),
                    'updated_by' => $page->updatedByUUID(),
                ]
            )
            ->execute();
    }

    public static function delete(Page $page)
    {
        DB::beginTransaction();
        // delete links
        DB::query()
            ->delete('page_link')
            ->where('start_page = :uuid OR end_page = :uuid', ['uuid' => $page->uuid()])
            ->execute();
        // delete alternate slugs
        DB::query()
            ->delete('page_slug')
            ->where('page_uuid = ?', [$page->uuid()])
            ->execute();
        // delete page
        DB::query()
            ->delete('page')
            ->where(
                'uuid = ? AND updated = ?',
                [
                    $page->uuid(),
                    $page->updatedLast()->format("Y-m-d H:i:s")
                ]
            )
            ->execute();
        // filter cache
        static::filterCache($page);
        DB::commit();
    }

    /**
     * Remove a given object from the object cache so that it will be recreated
     * if pulled again
     *
     * @param Page $page
     * @return void
     */
    protected static function filterCache(Page $page)
    {
        foreach (static::$cache as $i => $v) {
            if ($v->uuid() == $page->uuid()) {
                unset(static::$cache[$i]);
            }
        }
    }

    /**
     * Convert a raw row out of the database into a Page object. Will return
     * from the cache if the given uuid has been seen before.
     *
     * @param array $result
     * @return Page|null
     */
    public static function resultToPage(array $result): ?Page
    {
        if (!is_array($result)) {
            return null;
        }
        if (isset(static::$cache[$result['uuid']])) {
            return static::$cache[$result['uuid']];
        }
        if (false === ($data = json_decode($result['data'], true))) {
            throw new \Exception("Error decoding Page json data");
        }
        $class = static::objectClass($result);
        static::$cache[$result['uuid']] = new $class(
            $data,
            [
                'uuid' => $result['uuid'],
                'slug' => $result['slug'],
                'name' => $result['name'],
                'created' => new DateTime($result['created']),
                'created_by' => $result['created_by'],
                'updated' => new DateTime($result['updated']),
                'updated_by' => $result['updated_by'],
            ]
        );
        return static::$cache[$result['uuid']];
    }
}
