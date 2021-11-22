<?php

namespace DigraphCMS\Content\Blocks;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;

class Blocks
{
    protected static $cache = [];

    /**
     * Quickly determine whether a given UUID exists.
     * primary or alternate.
     *
     * @param string $uuid
     * @param string|null $page_uuid
     * @return Block|null
     */
    public static function exists(string $uuid, string $page_uuid = null): bool
    {
        $query = DB::query()->from('page_block')
            ->where('uuid = ?', [$uuid]);
        if ($page_uuid) {
            $query->where('page_uuid = ?', [$page_uuid]);
        }
        return !!$query->count();
    }

    /**
     * Generate a BlockSelect object for building queries to the pages table
     *
     * @param string|null $page_uuid
     * @return BlockSelect
     */
    public static function select(string $page_uuid = null): BlockSelect
    {
        $query = DB::query()->from('page_block');
        if ($page_uuid) {
            $query->where('page_uuid = ?',[$page_uuid]);
        }
        return new BlockSelect($query);
    }

    /**
     * Get all blocks that match the given UUID, and optionally page UUID
     * 
     * @param string $uuid
     * @param string|null $page_uuid
     * @return AbstractBlock|null
     */
    public static function get(string $uuid, string $page_uuid = null): ?AbstractBlock
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
        return AbstractBlock::class;
    }

    public static function update(AbstractBlock $block)
    {
        DB::beginTransaction();
        Dispatcher::dispatchEvent('onBeforeBlockUpdate', [$block]);
        Dispatcher::dispatchEvent('onBeforeBlockUpdate_' . $block->class(), [$block]);
        // update values
        DB::query()
            ->update('page_block')
            ->where(
                'uuid = ? AND updated = ?',
                [
                    $block->uuid(),
                    $block->updatedLast()->getTimestamp()
                ]
            )
            ->set([
                'data' => json_encode($block->get()),
                'class' => $block->class(),
                'updated' => time(),
                'updated_by' => Session::user()
            ])
            ->execute();
        Dispatcher::dispatchEvent('onAfterBlockUpdate_' . $block->class(), [$block]);
        Dispatcher::dispatchEvent('onAfterBlockUpdate', [$block]);
        DB::commit();
    }

    public static function insert(AbstractBlock $block)
    {
        // insert value
        Dispatcher::dispatchEvent('onBeforeBlockInsert', [$block]);
        Dispatcher::dispatchEvent('onBeforeBlockInsert_' . $block->class(), [$block]);
        DB::query()
            ->insertInto(
                'page_block',
                [
                    'uuid' => $block->uuid(),
                    'data' => json_encode($block->get()),
                    'class' => $block->class(),
                    'created' => time(),
                    'created_by' => $block->createdByUUID() ?? Session::user(),
                    'updated' => time(),
                    'updated_by' => $block->updatedByUUID() ?? Session::user(),
                ]
            )
            ->execute();
        Dispatcher::dispatchEvent('onAfterBlockInsert_' . $block->class(), [$block]);
        Dispatcher::dispatchEvent('onAfterBlockInsert', [$block]);
    }

    public static function delete(AbstractBlock $block)
    {
        DB::beginTransaction();
        // events
        Dispatcher::dispatchEvent('onBeforeBlockDelete', [$block]);
        Dispatcher::dispatchEvent('onBeforeBlockDelete_' . $block->class(), [$block]);
        // delete block
        DB::query()
            ->delete('page_block')
            ->where(
                'uuid = ? AND updated = ?',
                [
                    $block->uuid(),
                    $block->updatedLast()->format("Y-m-d H:i:s")
                ]
            )
            ->execute();
        // filter cache
        static::filterCache($block);
        // events
        Dispatcher::dispatchEvent('onAfterBlockDelete_' . $block->class(), [$block]);
        Dispatcher::dispatchEvent('onAfterBlockDelete', [$block]);
        // commit DB changes
        DB::commit();
    }

    /**
     * Remove a given object from the object cache so that it will be recreated
     * if pulled again
     *
     * @param AbstractBlock $block
     * @return void
     */
    protected static function filterCache(AbstractBlock $block)
    {
        foreach (static::$cache as $i => $v) {
            if ($v->uuid() == $block->uuid()) {
                unset(static::$cache[$i]);
            }
        }
    }

    /**
     * Convert a raw row out of the database into a block object. Will return
     * from the cache if the given uuid has been seen before.
     *
     * @param array $result
     * @return AbstractBlock|null
     */
    public static function resultToBlock(array $result): ?AbstractBlock
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
                'uuid' => $result['uuid'],
                'created' => (new DateTime)->setTimestamp($result['created']),
                'created_by' => $result['created_by'],
                'updated' => (new DateTime)->setTimestamp($result['updated']),
                'updated_by' => $result['updated_by'],
            ]
        );
        return static::$cache[$result['uuid']];
    }
}
