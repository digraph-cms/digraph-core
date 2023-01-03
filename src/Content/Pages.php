<?php

namespace DigraphCMS\Content;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;

class Pages
{
    protected static $cache = [];

    /**
     * Quickly determine whether a given UUID exists. Does not check slugs,
     * primary or alternate.
     *
     * @param string|null $uuid
     * @return boolean
     */
    public static function exists(?string $uuid): bool
    {
        if (!$uuid) return false;
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
     * Get all pages that match the given slug/UUID, including those indicated
     * by a slug. Significantly slower than get(). A UUID match will be first,
     * followed by primary slug matches, followed by alternate slug matches.
     * Within the groups pages are sorted oldest creation date first.
     *
     * @param string|null $uuid_or_slug
     * @return array
     */
    public static function getAll(?string $uuid_or_slug): array
    {
        // return empty for null
        if (!$uuid_or_slug) return [];
        // get best-case scenarios by uuid or primary slug
        $main = static::select()
            ->where('uuid = ?', [$uuid_or_slug])
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
     * @param string|null $uuid_or_slug
     * @return integer
     */
    public static function countAll(?string $uuid_or_slug): int
    {
        // return zero for null
        if (!$uuid_or_slug) return 0;
        // get best-case scenarios by uuid or primary slug
        $main = static::select()
            ->where('uuid = ?', [$uuid_or_slug])
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
     * @template T of AbstractPage
     * 
     * @param string|null $uuid_or_slug
     * @param class-string<T> $class
     * @return T|null
     */
    public static function get(?string $uuid_or_slug, string $class = AbstractPage::class): ?AbstractPage
    {
        if (!$uuid_or_slug) return null;
        if (!isset(static::$cache[$uuid_or_slug])) {
            static::$cache[$uuid_or_slug] =
                self::doGetByUUID($uuid_or_slug) ??
                self::doGetBySlug($uuid_or_slug);
        }
        if (static::$cache[$uuid_or_slug]) {
            if (static::$cache[$uuid_or_slug] instanceof $class) {
                return static::$cache[$uuid_or_slug];
            }
        }
        return null;
    }

    protected static function doGetByUUID(string $uuid_or_slug): ?AbstractPage
    {
        $result = DB::query()->from('page')
            ->where('uuid = ?', [$uuid_or_slug])
            ->order('created ASC')
            ->limit(1)
            ->execute();
        if ($result && $result = $result->fetch()) {
            return static::resultToPage($result);
        } else {
            return null;
        }
    }

    protected static function doGetBySlug(string $slug): ?AbstractPage
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
        return Config::get('page_types.' . $result['class']) ?? Config::get('page_types.default');
    }

    public static function update(AbstractPage $page)
    {
        DB::beginTransaction();
        Dispatcher::dispatchEvent('onBeforePageUpdate', [$page]);
        Dispatcher::dispatchEvent('onBeforePageUpdate_' . $page->class(), [$page]);
        // update values
        DB::query()
            ->update('page')
            ->where(
                'uuid = ? AND updated = ?',
                [
                    $page->uuid(),
                    $page->updatedLast()->getTimestamp()
                ]
            )
            ->set([
                'name' => $page->name(null, true, true),
                'sort_name' => $page->sortName(),
                'sort_weight' => $page->sortWeight(),
                'data' => json_encode($page->get()),
                'slug_pattern' => $page->slugPattern(),
                'class' => $page->class(),
                'updated' => time(),
                'updated_by' => Session::uuid()
            ])
            ->execute();
        Dispatcher::dispatchEvent('onAfterPageUpdate_' . $page->class(), [$page]);
        Dispatcher::dispatchEvent('onAfterPageUpdate', [$page]);
        DB::commit();
    }

    public static function insert(AbstractPage $page, string $parent_uuid = null, string $edge_type = null)
    {
        DB::beginTransaction();
        // pre-insert events
        Dispatcher::dispatchEvent('onBeforePageInsert', [$page]);
        Dispatcher::dispatchEvent('onBeforePageInsert_' . $page->class(), [$page]);
        // insert page
        DB::query()
            ->insertInto(
                'page',
                [
                    'uuid' => $page->uuid(),
                    'name' => $page->name(null, true, true),
                    'sort_name' => $page->sortName(),
                    'sort_weight' => $page->sortWeight(),
                    'data' => json_encode($page->get()),
                    'slug_pattern' => $page->slugPattern(),
                    'class' => $page->class(),
                    'created' => time(),
                    'created_by' => Session::uuid(),
                    'updated' => time(),
                    'updated_by' => Session::uuid(),
                ]
            )
            ->execute();
        // insert link if specified
        if ($parent_uuid) static::insertLink($parent_uuid, $page->uuid(), $edge_type);
        // post-insert events
        Dispatcher::dispatchEvent('onAfterPageInsert_' . $page->class(), [$page]);
        Dispatcher::dispatchEvent('onAfterPageInsert', [$page]);
        DB::commit();
    }

    public static function delete(AbstractPage $page)
    {
        DB::beginTransaction();
        // events
        Dispatcher::dispatchEvent('onBeforePageDelete', [$page]);
        Dispatcher::dispatchEvent('onBeforePageDelete_' . $page->class(), [$page]);
        // delete links
        DB::query()
            ->delete('page_link')
            ->where('start_page = ? OR end_page = ?', [$page->uuid(), $page->uuid()])
            ->execute();
        // delete slugs
        DB::query()
            ->delete('page_slug')
            ->where('page_uuid = ?', [$page->uuid()])
            ->execute();
        // delete page
        DB::query()
            ->delete('page')
            ->where(
                'uuid = ?',
                [
                    $page->uuid()
                ]
            )
            ->execute();
        // filter cache
        static::filterCache($page);
        // events
        Dispatcher::dispatchEvent('onAfterPageDelete_' . $page->class(), [$page]);
        Dispatcher::dispatchEvent('onAfterPageDelete', [$page]);
        // commit DB changes
        DB::commit();
        // onPageDeleted runs after DB commit, so that it can do destructive
        // operations only once everything else has completed successfully
        Dispatcher::dispatchEvent('onPageDeleted_' . $page->class(), [$page]);
        Dispatcher::dispatchEvent('onPageDeleted', [$page]);
    }

    /**
     * Remove a given object from the object cache so that it will be recreated
     * if pulled again
     *
     * @param AbstractPage $page
     * @return void
     */
    protected static function filterCache(AbstractPage $page)
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
     * @return AbstractPage|null
     */
    public static function resultToPage(array $result): ?AbstractPage
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
                'name' => $result['name'],
                'slug_pattern' => $result['slug_pattern'],
                'created' => (new DateTime)->setTimestamp($result['created']),
                'created_by' => $result['created_by'],
                'updated' => (new DateTime)->setTimestamp($result['updated']),
                'updated_by' => $result['updated_by'],
            ]
        );
        static::$cache[$result['uuid']]
            ->setSortName($result['sort_name'])
            ->setSortWeight($result['sort_weight']);
        return static::$cache[$result['uuid']];
    }
}
