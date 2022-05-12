<?php

namespace DigraphCMS\Content;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use Envms\FluentPDO\Queries\Select;

class Graph
{
    public static function parentIDs(string $uuid, string $type = null): Select
    {
        $query = DB::query()
            ->from('page_link')
            ->leftJoin('page on page_link.start_page = page.uuid')
            ->select('start_page')
            ->where('end_page = ?', [$uuid]);
        if ($type) {
            $query->where('page_link.type = ?', [$type]);
        }
        return $query;
    }

    public static function parents(string $uuid, string $type = null): PageSelect
    {
        $query = DB::query()
            ->from('page_link')
            ->leftJoin('page on page_link.start_page = page.uuid')
            ->select('page.*')
            ->where('end_page = ?', [$uuid]);
        if ($type) {
            $query->where('page_link.type = ?', [$type]);
        }
        return new PageSelect($query);
    }

    public static function childIDs(string $uuid, string $type = null): Select
    {
        $query = DB::query()
            ->from('page_link')
            ->leftJoin('page on page_link.start_page = page.uuid')
            ->select('end_page')
            ->where('start_page = ?', [$uuid]);
        if ($type) {
            $query->where('page_link.type = ?', [$type]);
        }
        return $query;
    }

    public static function randomChildID(string $uuid, string $type = null): ?string
    {
        $query = static::childIDs($uuid, $type)
            ->limit(1);
        if (Config::get('db.adapter') == 'sqlite') {
            $query->order('RANDOM()');
        } else {
            $query->order('RAND()');
        }
        $result = $query->fetch();
        return $result ? $result['end_page'] : null;
    }

    public static function randomParentID(string $uuid, string $type = null): ?string
    {
        $query = static::parentIDs($uuid, $type)
            ->limit(1);
        if (Config::get('db.adapter') == 'sqlite') {
            $query->order('RANDOM()');
        } else {
            $query->order('RAND()');
        }
        $result = $query->fetch();
        return $result ? $result['start_page'] : null;
    }

    public static function children(string $uuid, string $type = null): PageSelect
    {
        $query = DB::query()
            ->from('page_link')
            ->leftJoin('page on page_link.end_page = page.uuid')
            ->select('page.*')
            ->where('start_page = ?', [$uuid]);
        if ($type) {
            $query->where('page_link.type = ?', [$type]);
        }
        return new PageSelect($query);
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
}
