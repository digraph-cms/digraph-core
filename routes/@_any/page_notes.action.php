<?php

use DigraphCMS\Content\PageNotes;
use DigraphCMS\Context;
use DigraphCMS\Notes\Note;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

$page = Context::page();

echo '<h1>Page notes</h1>';
printf(
    '<p>These notes are only visible to users with permissions to edit the page %s. They can be used to store any historical or contextual information about the page that may be useful to future editors.</p>',
    $page->url()->html()
);
printf('<p><a href="%s">Add a new note</a></p>', $page->url('_new_page_note'));

$notes = PageNotes::group($page);

$table = new PaginatedTable(
    $notes->select(),
    function (Note $note): array {
        $side = [
            '<strong>created</strong>',
            Format::date($note->created()),
            $note->createdBy(),
        ];
        if ($note->created() != $note->updated()) {
            $side[] = '<strong>updated</strong>';
            $side[] = Format::date($note->updated());
            $side[] = $note->updatedBy();
        }
        $side[] = sprintf('<a href="%s">edit</a>', new URL('edit_note:' . $note->uuid()));
        return [
            sprintf('<p><strong>%s</strong></p>%s', $note->title(), $note->html()),
            implode('<br>', $side),
        ];
    }
);

echo $table;
