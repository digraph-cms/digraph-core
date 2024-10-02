<?php

use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Notes\Notes;
use DigraphCMS\Notes\NotesForm;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

$form = new NotesForm(Notes::namespace('debug')->group('debug'));

echo $form;

if ($form->ready()) {
    Notifications::flashConfirmation('Note created');
    throw new RedirectException(new URL('note:' . $form->note->uuid()));
}
