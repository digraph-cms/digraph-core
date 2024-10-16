<?php

namespace DigraphCMS\Content;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\DB\SubValueIterator;
use DigraphCMS\Events\Dispatcher;
use Envms\FluentPDO\Queries\Select;

class Graph
{
    public static function route(string $start, string $end, string $type = null, array $visited = []): ?array
    {
        // special case when they're the same
        if ($start == $end) return [$start];
        // start at the end and work backwards
        foreach (static::parentEdges($end, $type) as $r) {
            if (in_array($r['start_page'], $visited)) return null;
            $visited[] = $r['start_page'];
            if ($r['start_page'] == $start) return [$start, $end];
            if ($route = static::route($start, $r['start_page'])) {
                $route[] = $end;
                return $route;
            }
        }
        return null;
    }

    public static function parentEdges(string $uuid, string $type = null): Select
    {
        $query = DB::query()
            ->from('page_link')
            ->leftJoin('page on page_link.start_page = page.uuid')
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

    public static function childEdges(string $uuid, string $type = null): Select
    {
        $query = DB::query()
            ->from('page_link')
            ->leftJoin('page on page_link.start_page = page.uuid')
            ->where('start_page = ?', [$uuid]);
        if ($type) {
            $query->where('page_link.type = ?', [$type]);
        }
        return $query;
    }

    public static function childIDs(string $uuid, string $type = null): SubValueIterator
    {
        return new SubValueIterator(static::childEdges($uuid, $type), 'end_page');
    }

    public static function parentIDs(string $uuid, string $type = null): SubValueIterator
    {
        return new SubValueIterator(static::parentEdges($uuid, $type), 'start_page');
    }

    public static function randomChildID(string $uuid, string $type = null): ?string
    {
        $query = static::childEdges($uuid, $type)
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
        $query = static::parentEdges($uuid, $type)
            ->limit(1);
        if (Config::get('db.adapter') == 'sqlite') {
            $query->order('RANDOM()');
        } else {
            $query->order('RAND()');
        }
        $result = $query->fetch();
        return $result ? $result['start_page'] : null;
    }

    public static function children(string $uuid, string|array|null $type = null, bool $sorted = true): PageSelect
    {
        $query = DB::query()
            ->from('page_link')
            ->leftJoin('page on page_link.end_page = page.uuid')
            ->select('page.*')
            ->where('start_page', $uuid);
        if ($type) {
            $query->where('page_link.type', $type);
        }
        if ($sorted) {
            $query->order('page.sort_weight ASC');
            $query->order('COALESCE(page.sort_name, page.name) ASC');
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
        DB::query()->insertInto(
            'page_link',
            [
                'start_page' => $start,
                'end_page' => $end,
                'type' => $type
                    ?? static::defaultLinkTypeByUuid($start, $end)
                    ?? 'normal'
            ]
        )->execute();
    }

    public static function defaultLinkTypeByUuid(string $start, string $end): null|string
    {
        $start = Pages::get($start);
        $end = Pages::get($end);
        if (!$start || !$end) return null;
        return static::defaultLinkType($start->class(), $end->class());
    }

    public static function defaultLinkType(string $start_type, string $end_type): null|string
    {
        return Dispatcher::firstValue('onLinkType', [$start_type, $end_type])
            ?? Dispatcher::firstValue(sprintf('onLinkType_%s', $start_type), [$end_type])
            ?? Dispatcher::firstValue(sprintf('onLinkType_%s_to_%s', $start_type, $end_type), []);
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
        $query->execute();
    }
}
