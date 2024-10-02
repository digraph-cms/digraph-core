<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Notes\Notes;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\URL\URL;

$note = Notes::get('debug', 'debug', Context::url()->actionSuffix());
if (!$note) throw new HttpError(404);

ActionMenu::addContextAction(
    new URL('edit:' . $note->uuid()),
    'Edit note',
);

printf('<h1>Note: %s</h1>', $note->title());
echo $note->html();
