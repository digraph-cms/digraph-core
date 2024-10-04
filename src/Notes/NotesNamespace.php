<?php

namespace DigraphCMS\Notes;

use DigraphCMS\Datastore\DatastoreNamespace;
use Envms\FluentPDO\Queries\Select;

/**
 * NotesNamespace provides a way to interact with a specific namespace of notes,
 * such as notes about pages, or notes about users.
 */
class NotesNamespace
{
    protected readonly DatastoreNamespace $data;

    public function __construct(
        public readonly string $name
    ) {
        $this->data = new DatastoreNamespace('notes_' . $name);
    }

    /**
     * Get a list of notes for a specific ID within this namespace, such as
     * notes about a specific page or user.
     */
    public function group(string $id): NotesGroup
    {
        return new NotesGroup($this, $id);
    }

    /**
     * Get a specific note by ID and key.
     */
    public function get(string $id, string $key): ?Note
    {
        return $this->group($id)->get($key);
    }

    /**
     * Get a specific note by datastore ID.
     */
    public function getByDatastoreId(int $id): ?Note
    {
        return $this->select()
            ->where('`id`', $id)
            ->fetch()
            ?: null;
    }

    /**
     * @return Select<Note>
     */
    public function select(): Select
    {
        return $this->data->select()
            ->query()
            ->order('created DESC')
            // @phpstan-ignore-next-line
            ->asObject(Note::class);
    }
}
