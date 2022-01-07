<?php

namespace DigraphCMS\RichMedia;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\Session\Session;

class RichMedia
{
    protected static $cache = [];

    /**
     * Quickly determine whether a given UUID exists.
     * primary or alternate.
     *
     * @param string $uuid
     * @param string|null $page_uuid
     * @return RichMedia|null
     */
    public static function exists(string $uuid, string $page_uuid = null): bool
    {
        $query = DB::query()->from('rich_media')
            ->where('uuid = ?', [$uuid]);
        if ($page_uuid) {
            $query->where('page_uuid = ?', [$page_uuid]);
        }
        return !!$query->count();
    }

    /**
     * Generate a RichMediaSelect object for building queries to the pages table
     *
     * @param string|null $page_uuid
     * @return RichMediaSelect
     */
    public static function select(string $page_uuid = null): RichMediaSelect
    {
        $query = DB::query()->from('rich_media');
        if ($page_uuid) {
            $query->where('page_uuid = ?', [$page_uuid]);
        }
        return new RichMediaSelect($query);
    }

    /**
     * Get all Media that match the given UUID, and optionally page UUID
     * 
     * @param string $uuid
     * @param string|null $page_uuid
     * @return AbstractRichMedia|null
     */
    public static function get(string $uuid, string $page_uuid = null): ?AbstractRichMedia
    {
        if (!isset(static::$cache[$uuid])) {
            $query = static::select()
                ->where('uuid = ?', [$uuid])
                ->limit(1);
            static::$cache[$uuid] = $query->fetch();
        }
        if ($page_uuid && static::$cache[$uuid]) {
            if (static::$cache[$uuid]->pageUUID() != $page_uuid) {
                return null;
            }
        }
        return static::$cache[$uuid];
    }

    public static function objectClass(array $result): string
    {
        if ($type = Config::get('rich_media_types.' . $result['class'])) {
            return $type;
        } else {
            throw new \Exception('Unknown rich media type');
        }
    }

    public static function update(AbstractRichMedia $media)
    {
        DB::beginTransaction();
        Dispatcher::dispatchEvent('onBeforeRichMediaUpdate', [$media]);
        Dispatcher::dispatchEvent('onBeforeRichMediaUpdate_' . $media->class(), [$media]);
        // update values
        DB::query()
            ->update('rich_media')
            ->where(
                'uuid = ? AND updated = ?',
                [
                    $media->uuid(),
                    $media->updatedLast()->getTimestamp()
                ]
            )
            ->set([
                'data' => json_encode($media->get()),
                'class' => $media->class(),
                'name' => $media->name(),
                'page_uuid' => $media->pageUUID(),
                'updated' => time(),
                'updated_by' => Session::user()
            ])
            ->execute();
        Dispatcher::dispatchEvent('onAfterRichMediaUpdate_' . $media->class(), [$media]);
        Dispatcher::dispatchEvent('onAfterRichMediaUpdate', [$media]);
        DB::commit();
    }

    public static function insert(AbstractRichMedia $media)
    {
        // insert value
        Dispatcher::dispatchEvent('onBeforeRichMediaInsert', [$media]);
        Dispatcher::dispatchEvent('onBeforeRichMediaInsert_' . $media->class(), [$media]);
        DB::query()
            ->insertInto(
                'rich_media',
                [
                    'uuid' => $media->uuid(),
                    'data' => json_encode($media->get()),
                    'class' => $media->class(),
                    'name' => $media->name(),
                    'page_uuid' => $media->pageUUID(),
                    'created' => time(),
                    'created_by' => $media->createdByUUID() ?? Session::user(),
                    'updated' => time(),
                    'updated_by' => $media->updatedByUUID() ?? Session::user(),
                ]
            )
            ->execute();
        Dispatcher::dispatchEvent('onAfterRichMediaInsert_' . $media->class(), [$media]);
        Dispatcher::dispatchEvent('onAfterRichMediaInsert', [$media]);
    }

    public static function delete(AbstractRichMedia $media)
    {
        DB::beginTransaction();
        // events
        Dispatcher::dispatchEvent('onBeforeRichMediaDelete', [$media]);
        Dispatcher::dispatchEvent('onBeforeRichMediaDelete_' . $media->class(), [$media]);
        // delete block
        DB::query()
            ->delete('rich_media')
            ->where(
                'uuid = ? AND updated = ?',
                [
                    $media->uuid(),
                    $media->updatedLast()->format("Y-m-d H:i:s")
                ]
            )
            ->execute();
        // filter cache
        static::filterCache($media);
        // events
        Dispatcher::dispatchEvent('onAfterRichMediaDelete_' . $media->class(), [$media]);
        Dispatcher::dispatchEvent('onAfterRichMediaDelete', [$media]);
        // commit DB changes
        DB::commit();
    }

    /**
     * Remove a given object from the object cache so that it will be recreated
     * if pulled again
     *
     * @param AbstractRichMedia $media
     * @return void
     */
    protected static function filterCache(AbstractRichMedia $media)
    {
        foreach (static::$cache as $i => $v) {
            if ($v->uuid() == $media->uuid()) {
                unset(static::$cache[$i]);
            }
        }
    }

    /**
     * Convert a raw row out of the database into a block object. Will return
     * from the cache if the given uuid has been seen before.
     *
     * @param array $result
     * @return AbstractRichMedia|null
     */
    public static function resultToMedia(array $result): ?AbstractRichMedia
    {
        if (!is_array($result)) {
            return null;
        }
        if (isset(static::$cache[$result['uuid']])) {
            return static::$cache[$result['uuid']];
        }
        if (false === ($data = json_decode($result['data'], true))) {
            throw new \Exception("Error decoding block json data");
        }
        $class = static::objectClass($result);
        static::$cache[$result['uuid']] = new $class(
            $data,
            [
                'class' => $result['class'],
                'name' => $result['name'],
                'uuid' => $result['uuid'],
                'page_uuid' => $result['page_uuid'],
                'created' => (new DateTime)->setTimestamp($result['created']),
                'created_by' => $result['created_by'],
                'updated' => (new DateTime)->setTimestamp($result['updated']),
                'updated_by' => $result['updated_by'],
            ]
        );
        return static::$cache[$result['uuid']];
    }
}
