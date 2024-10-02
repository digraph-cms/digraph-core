<?php

namespace DigraphCMS\Notes;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\SafeContent\SafeBBCodeField;
use Stringable;

class NotesForm implements Stringable
{
    public readonly FormWrapper $form;
    /** @var Note|null the Note that was created/updated */
    public Note|null $note = null;

    public function addCallback(callable $callback)
    {
        $this->form->addCallback($callback);
    }

    public function ready(): bool
    {
        return $this->form->ready();
    }

    public function __construct(
        NotesGroup|Note $note_or_group,
    ) {
        // figure out what we have
        if ($note_or_group instanceof Note) {
            $note = $note_or_group;
            $group = $note->group();
        } else {
            $group = $note_or_group;
            $note = null;
        }
        // set up form
        $this->form = new FormWrapper();
        $title = (new Field('Title'))
            ->setRequired(true)
            ->addForm($this->form);
        $text = (new SafeBBCodeField('Note'))
            ->setRequired(true)
            ->addForm($this->form);
        if ($note) {
            // set up form for specific note
            $this->form->setID(md5(serialize([
                $note->namespace()->name,
                $note->group()->name,
                $note->uuid()
            ])));
            $this->form->button()->setText('Update note');
            $title->setDefault($note->title(true));
            $text->setDefault($note->text());
            // callback to update note
            $this->form->addCallback(function () use ($title, $text, $note) {
                $note->update(
                    strip_tags($title->value()),
                    $text->value()
                );
                $this->note = $note;
            });
        } else {
            // set up form for creating a new note
            $this->form->setID(md5(serialize([
                $group->namespace->name,
                $group->name
            ])));
            $this->form->button()->setText('Add note');
            // callback to create note
            $this->form->addCallback(function () use ($title, $text, $group) {
                $this->note = $group->create(
                    strip_tags($title->value()),
                    $text->value()
                );
            });
        }
    }

    public function __toString()
    {
        return $this->form->__toString();
    }
}
