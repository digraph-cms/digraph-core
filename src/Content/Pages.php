<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractDataObject;
use DigraphCMS\DB\AbstractDataObjectSource;
use DigraphCMS\DB\DataObjectSelect;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;
use Envms\FluentPDO\Queries\Select;

class Pages extends AbstractDataObjectSource
{
    const TABLE = 'pages';
    const COLNAMES = [
        'uuid' => 'page_uuid',
        'slug' => 'page_slug',
        'data' => 'page_data',
        'class' => 'page_class',
        'created' => 'created',
        'created_by' => 'created_by',
        'updated' => 'updated',
        'updated_by' => 'updated_by',
    ];

    /**
     * Get the child Pages of a given Page uuid
     *
     * @param string $start
     * @param string $order
     * @return DataObjectSelect
     */
    public static function children(string $start, string $order = 'created ASC'): DataObjectSelect
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
     * Undocumented function
     *
     * @param string $uuid_or_slug
     * @return boolean
     */
    public static function validateSlug(string $uuid_or_slug): bool
    {
        return !self::slugExists($uuid_or_slug) && !self::exists($uuid_or_slug);
    }

    public static function slugExists(string $uuid_or_slug): bool
    {
        $query = DB::query()->from(static::TABLE)
            ->where('page_uuid = :q OR page_slug = :q', [':q' => $uuid_or_slug]);
        return !!$query->count();
    }

    public function getAll(string $uuid_or_slug): DataObjectSelect
    {
        $select = $this->select();
        $select->where('page_uuid = :q OR page_slug = :q', [':q' => $uuid_or_slug]);
        $select->order('CASE WHEN :q = page_uuid THEN 0 ELSE 1 END ASC, created ASC');
        // TODO: get by alias and append (will require multiple DB calls and returning an array)
        return $select;
    }

    public static function get(string $uuid_or_slug): ?AbstractDataObject
    {
        if (!isset(static::$cache[$uuid_or_slug])) {
            static::$cache[$uuid_or_slug] =
                self::doGet($uuid_or_slug) ??
                // TODO: get by alias if necessary
                Dispatcher::firstValue('onGetPage', [$uuid_or_slug]);
        }
        return static::$cache[$uuid_or_slug];
    }

    protected static function doGet(string $uuid_or_slug): ?AbstractDataObject
    {
        $query = DB::query()->from('pages')
            ->where('page_uuid = :q OR page_slug = :q', [':q' => $uuid_or_slug])
            ->order('CASE WHEN :q = page_uuid THEN 0 ELSE 1 END ASC, created ASC')
            ->limit(1);
        $result = $query->execute();
        if ($result && $result = $result->fetch()) {
            return static::resultToObject($result);
        } else {
            return null;
        }
    }

    public static function objectClass(array $result): string
    {
        return Page::class;
    }

    protected static function insertObjectValues(AbstractDataObject $object): array
    {
        return [
            static::COLNAMES['uuid'] => $object->uuid(),
            static::COLNAMES['slug'] => $object->slug(),
            static::COLNAMES['data'] => json_encode($object->get()),
            static::COLNAMES['class'] => $object->class(),
            static::COLNAMES['created_by'] => $object->createdBy(),
            static::COLNAMES['updated_by'] => $object->updatedBy(),
        ];
    }

    protected static function updateObjectValues(AbstractDataObject $object): array
    {
        return [
            static::COLNAMES['slug'] => $object->slug(),
            static::COLNAMES['data'] => json_encode($object->get()),
            static::COLNAMES['class'] => $object->class(),
            static::COLNAMES['updated_by'] => Session::user()
        ];
    }
}

Pages::__init();
