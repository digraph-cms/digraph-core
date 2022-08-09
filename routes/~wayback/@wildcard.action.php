<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\WaybackMachine;

Context::response()->enableCache();
Context::response()->headers()->set('X-Robots-Tag', 'noindex');

if (!($result = WaybackMachine::getByHash(Context::url()->action()))) {
    throw new HttpError(404);
}

if (Context::arg('context')) {
    Breadcrumb::parent(new URL(Context::arg('context')));
}

echo Templates::render(
    'content/wayback.php',
    ['wb' => $result]
);
