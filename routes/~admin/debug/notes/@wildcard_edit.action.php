<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Notes\Notes;
use DigraphCMS\Notes\NotesForm;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

$note = Notes::get('debug', 'debug', Context::url()->actionSuffix());
if (!$note) throw new HttpError(404);

ActionMenu::addContextAction(
    new URL('note:' . $note->uuid()),
    'View note',
);

$form = new NotesForm($note);

echo $form;

if ($form->ready()) {
    Notifications::flashConfirmation('Note updated');
    throw new RedirectException(new URL('note:' . $note->uuid()));
}
