<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\WaybackMachine;

Context::response()->enableCache();
Context::response()->headers()->set('X-Robots-Tag', 'noindex');

if (!($result = WaybackMachine::getByUUID(Context::url()->action()))) {
    throw new HttpError(404);
}

echo Templates::render(
    'content/wayback.php',
    ['wb' => $result]
);
