<?php

namespace DigraphCMS\Notes;

use DigraphCMS\Datastore\Datastore;
use DigraphCMS\DB\DB;
use Envms\FluentPDO\Queries\Select;

/**
 * Notes provides a general-purpose way to store notes about objects. It is
 * designed to allow easy use for a variety of cases, such as notes about pages,
 * users, content within pages, anything that can be grouped into a namespace
 * and identified by some unique value. Notes are generally intended to be
 * immutable and append-only, as they are meant to be used in situations where
 * multiple users may be referencing them over a long time scale.
 */
class Notes
{
    public static function namespace(string $namespace): NotesNamespace
    {
        return new NotesNamespace($namespace);
    }

    public static function get(string $namespace, string $id, string $key): ?Note
    {
        return self::namespace($namespace)->get($id, $key);
    }

    /**
     * @return Select<Note>
     */
    public static function list(): Select
    {
        return DB::query()
            ->from('datastore')
            ->where('ns LIKE "notes_%"')
            // @phpstan-ignore-next-line
            ->asObject(Note::class);
    }
}
