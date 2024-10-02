<?php

namespace DigraphCMS\Notes;

use DigraphCMS\Datastore\DatastoreGroup;
use DigraphCMS\Digraph;
use Envms\FluentPDO\Queries\Select;

/**
 * NotesList provides a way to interact with a specific group of notes, such as
 * notes about a specific page or user.
 */
class NotesGroup
{
    protected readonly DatastoreGroup $data;

    public function __construct(
        public readonly NotesNamespace $namespace,
        public readonly string $name,
    ) {
        $this->data = new DatastoreGroup('notes_' . $namespace->name, $name);
    }

    /**
     * Create a new note
     */
    public function create(
        string $title,
        string $text,
    ): Note {
        $uuid = Digraph::uuid();
        $this->data->set(
            $uuid,
            $title,
            [
                'text' => $text,
            ]
        );
        return $this->get($uuid);
    }

    /**
     * Get a specific note by key.
     */
    public function get(string $key): ?Note
    {
        return $this->select()
            ->where('key', $key)
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
