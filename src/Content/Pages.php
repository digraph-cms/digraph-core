<?php

namespace DigraphCMS\Content;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;
use Envms\FluentPDO\Queries\Select;

Dispatcher::addSubscriber(Pages::class);

class Pages
{
    const SLUG_CHARS = 'a-zA-Z0-9\\-_';
    protected static $cache = [];

    public static function validateSlug(string $slug): bool
    {
        return preg_match("@^[" . static::SLUG_CHARS . "]*(/[" . static::SLUG_CHARS . "]+)*$@", $slug);
    }

    public static function exists(string $uuid): bool
    {
        $query = DB::query()->from('pages')
            ->where('page_uuid = ?', [$uuid]);
        return !!$query->count();
    }

    public static function select(): PageSelect
    {
        return new PageSelect(DB::query()->from('pages'), static::class);
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
        $query->leftJoin('links ON link_end = page_uuid');
        $query->where('link_start = ?', [$start]);
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
        $query = DB::query()->from('links');
        $query->where('link_start = ?', [$start]);
        $query->order($order);
        return $query->execute()->fetchColumn('link_end');
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
            'links',
            [
                'link_start' => $start,
                'link_end' => $end,
                'link_end' => $type ?? 'normal'
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
        $query = DB::query()->deleteFrom('links');
        $query->where('link_start = ? AND link_end = ?', [$start, $end]);
        if ($type) {
            $query->where('link_type = ?', [$type]);
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
        $query = DB::query()->from('pages')
            ->where('page_uuid = :q OR page_slug = :q', [':q' => $uuid_or_slug]);
        return !!$query->count();
    }

    /**
     * Get all pages that match the given slug/UUID, including those indicated
     * by an alias. Significantly slower than get(). A UUID match will be first,
     * followed by slug matches, followed by alias matches. Within slugs and
     * aliases pages are sorted by creation date, with older pages first.
     *
     * @param string $uuid_or_slug
     * @return PageSelect
     */
    public static function getAll(string $uuid_or_slug): PageSelect
    {
        $select = static::select();
        $select->where('page_uuid = :q OR page_slug = :q', [':q' => $uuid_or_slug]);
        $select->order('CASE WHEN :q = page_uuid THEN 0 ELSE 1 END ASC, created ASC');
        // TODO: get by alias and append (will require multiple DB calls and returning an array)
        return $select;
    }

    /**
     * Get the top result for a given slug/UUID. Will be fastest for UUID and
     * slug matches, but will run an additional query and search aliases if
     * UUID or slug matches are not found. A UUID match will take precedence,
     * followed by the oldest creation date slug match, followed by the oldest
     * alias match.
     *
     * @param string $uuid_or_slug
     * @return Page|null
     */
    public static function get(string $uuid_or_slug): ?Page
    {
        if (!isset(static::$cache[$uuid_or_slug])) {
            static::$cache[$uuid_or_slug] =
                self::doGet($uuid_or_slug) ??
                // TODO: get by alias if necessary
                Dispatcher::firstValue('onGetPage', [$uuid_or_slug]);
        }
        return static::$cache[$uuid_or_slug];
    }

    protected static function doGet(string $uuid_or_slug): ?Page
    {
        $query = DB::query()->from('pages')
            ->where('page_uuid = :q OR page_slug = :q', [':q' => $uuid_or_slug])
            ->order('CASE WHEN :q = page_uuid THEN 0 ELSE 1 END ASC, created ASC')
            ->limit(1);
        $result = $query->execute();
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

    public static function update(Page $page)
    {
        //TODO: insert alias if slug updated
        // update values
        $query = DB::query()->update('pages');
        $query->where(
            'page_uuid = ? AND updated = ?',
            [
                $page->uuid(),
                $page->updatedLast()->format("Y-m-d H:i:s")
            ]
        )
            ->set(static::updateObjectValues($page))
            ->execute();
    }

    public static function insert(Page $page)
    {
        // insert value
        $query = DB::query();
        $query->insertInto(
            'pages',
            static::insertObjectValues($page)
        )->execute();
    }

    public static function delete(Page $page)
    {
        //TODO: delete links
        //TODO: delete linked aliases
        // delete object
        $query = DB::query()->delete('pages');
        $query->where(
            'page_uuid = ? AND updated = ?',
            [
                $page->uuid(),
                $page->updatedLast()->format("Y-m-d H:i:s")
            ]
        )->execute();
        static::filterCache($page);
    }

    protected static function insertObjectValues(Page $page): array
    {
        return [
            'page_uuid' => $page->uuid(),
            'page_slug' => $page->slug(),
            'page_data' => json_encode($page->get()),
            'page_class' => $page->class(),
            'created_by' => $page->createdBy(),
            'updated_by' => $page->updatedBy(),
        ];
    }

    protected static function updateObjectValues(Page $page): array
    {
        return [
            'page_slug' => $page->slug(),
            'page_data' => json_encode($page->get()),
            'page_class' => $page->class(),
            'updated_by' => Session::user()
        ];
    }

    /**
     * Remove a given object from the 
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

    public static function resultToPage($result): ?Page
    {
        if (!is_array($result)) {
            return null;
        }
        if (isset(static::$cache[$result['page_uuid']])) {
            return static::$cache[$result['page_uuid']];
        }
        if ('page_data') {
            if (false === ($data = json_decode($result['page_data'], true))) {
                throw new \Exception("Error decoding Page json data");
            }
        } else {
            $data = [];
        }
        $class = static::objectClass($result);
        static::$cache[$result['page_uuid']] = new $class(
            $data,
            [
                'uuid' => $result['page_uuid'],
                'slug' => $result['page_slug'],
                'created' => new DateTime($result['created']),
                'created_by' => $result['created_by'],
                'updated' => new DateTime($result['updated']),
                'updated_by' => $result['updated_by'],
            ]
        );
        return static::$cache[$result['page_uuid']];
    }
}
