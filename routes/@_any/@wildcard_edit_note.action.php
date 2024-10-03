<?php

use DigraphCMS\Content\PageNotes;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Notes\NotesForm;
use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\Permissions;

$page = Context::page();

$notes = PageNotes::group($page);
$note = $notes->get(Context::url()->actionSuffix());

if (!$note) throw new HttpError(404);

$form = new NotesForm($note);

echo $form;

if ($form->ready()) {
    Notifications::flashConfirmation('Note updated');
    throw new RedirectException($page->url('page_notes'));
}

if (Permissions::inGroup('admins')) {
    printf(
        '<hr><p><a href="%s">Datastore view</a></p>',
        $note->url_admin(),
    );
}
