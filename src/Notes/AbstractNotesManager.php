<?php

namespace DigraphCMS\Notes;

use Envms\FluentPDO\Queries\Select;

/**
 * A class intended to make easy work of managing Notes for a particular
 * purpose, through a one-stop interface.
 * 
 * @template E of mixed
 */
abstract class AbstractNotesManager
{
    /**
     * The namespace within which this notes manager operates.
     */
    abstract protected static function ns(): string;

    /**
     * Given a parent element, convert it to a group name to use to store notes
     * about it.
     * @param E $parent
     */
    abstract protected static function groupName(mixed $parent): string;

    /**
     * Get the NotesGroup object for a given parent element.
     * @param E $parent
     */
    public static function group(mixed $parent): NotesGroup
    {
        return new NotesGroup(static::namespace(), static::groupName($parent));
    }

    /**
     * Get all notes for a given parent element.
     * @param E $parent
     * @return Select<Note>
     */
    public static function select(mixed $parent): Select
    {
        return static::group($parent)->select();
    }

    /**
     * Get all notes in this manager.
     * @return Select<Note>
     */
    public static function selectAll(): Select
    {
        return static::namespace()->select();
    }

    /**
     * Generate a form for either adding a note to a parent element, or editing
     * an existing note.
     * @param E|Note $parent_or_note
     */
    public static function form(mixed $parent_or_note): NotesForm
    {
        if ($parent_or_note instanceof Note) {
            return new NotesForm($parent_or_note);
        } else {
            return new NotesForm(static::group($parent_or_note));
        }
    }

    public static function namespace(): NotesNamespace
    {
        return new NotesNamespace(static::ns());
    }
}
