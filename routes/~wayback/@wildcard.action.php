<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Templates;

Context::response()->enableCache();
Context::response()->headers()->set('X-Robots-Tag', 'noindex');

$row = DB::query()->from('wayback')
    ->where('uuid = ?', [Context::url()->action()])
    ->fetch();

if (!$row) {
    throw new HttpError(404);
}

$data = json_decode($row['data'], true);

echo Templates::render(
    'content/wayback.php',
    ['row' => $row, 'data' => $data]
);
