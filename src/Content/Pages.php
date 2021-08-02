<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractDataObject;
use DigraphCMS\DB\AbstractDataObjectSource;
use DigraphCMS\DB\DataObjectSelect;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;

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

    public static function validateSlug(string $uuid_or_slug): bool
    {
        return !self::exists($uuid_or_slug) && !self::uuidExists($uuid_or_slug);
    }

    public static function uuidExists(string $uuid): bool
    {
        $query = DB::query()->from(static::TABLE)
            ->where('page_uuid = ?', [$uuid])
            ->order('created ASC');
        return !!$query->count();
    }

    protected static function doExists(string $uuid_or_slug): bool
    {
        $query = DB::query()->from(static::TABLE)
            ->where('page_uuid = :q OR page_slug = :q', [':q' => $uuid_or_slug])
            ->order('created ASC')
            ->limit(1);
        return !!$query->count();
    }

    public function search(string $uuid_or_slug): DataObjectSelect
    {
        $select = $this->select();
        $select->where('page_uuid = :q OR page_slug = :q', [':q' => $uuid_or_slug]);
        $select->order('CASE WHEN :q = page_uuid THEN 0 ELSE 1 END ASC, created ASC');
        return $select;
    }

    public static function get(string $uuid_or_slug): ?AbstractDataObject
    {
        if (!isset(static::$cache[$uuid_or_slug])) {
            static::$cache[$uuid_or_slug] =
                self::doGet($uuid_or_slug) ??
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
