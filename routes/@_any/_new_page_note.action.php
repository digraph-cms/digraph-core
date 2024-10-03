<h1>Add page note</h1>
<?php

use DigraphCMS\Content\PageNotes;
use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Notes\NotesForm;
use DigraphCMS\UI\Notifications;

$page = Context::page();
$notes = PageNotes::group($page);

$form = new NotesForm($notes);

echo $form;

if ($form->ready()) {
    Notifications::flashConfirmation('Note added');
    throw new RedirectException($page->url('page_notes'));
}
