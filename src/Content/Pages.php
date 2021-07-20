<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractDataObject;
use DigraphCMS\DB\AbstractDataObjectSource;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;

class Pages extends AbstractDataObjectSource
{
    const TABLE = 'pages';
    const COLNAMES = [
        'uuid' => 'page_uuid',
        'data' => 'page_data',
        'class' => 'page_class',
        'created' => 'created',
        'created_by' => 'created_by',
        'updated' => 'updated',
        'updated_by' => 'updated_by',
    ];

    public static function get(string $uuid_or_alias): ?AbstractDataObject
    {
        if (!isset(static::$cache[$uuid_or_alias])) {
            static::$cache[$uuid_or_alias] = Dispatcher::firstValue('onGetPage', [$uuid_or_alias]) ?? self::doGet($uuid_or_alias);
        }
        return static::$cache[$uuid_or_alias];
    }

    protected static function doGet(string $uuid_or_alias): ?AbstractDataObject
    {
            $query = DB::query()->from('pages')
                ->limit(1)
                ->leftJoin('aliases on page_uuid = alias_page')
                ->where('page_uuid = :q OR alias_slug = :q', ['q' => $uuid_or_alias]);
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
            static::COLNAMES['data'] => json_encode($object->get()),
            static::COLNAMES['class'] => $object->class(),
            static::COLNAMES['created_by'] => $object->createdBy(),
            static::COLNAMES['updated_by'] => $object->updatedBy(),
        ];
    }

    protected static function updateObjectValues(AbstractDataObject $object): array
    {
        return [
            static::COLNAMES['data'] => json_encode($object->get()),
            static::COLNAMES['class'] => $object->class(),
            static::COLNAMES['updated_by'] => Session::user()
        ];
    }
}

Pages::__init();
