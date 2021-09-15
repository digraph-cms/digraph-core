<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;

if (Context::arg('csrf') !== Cookies::csrfToken('editor')) {
    throw new HttpError(401);
}

Context::response()->private(true);
Context::response()->filename('response.json');

if (!$_FILES['file']['tmp_name']) {
    throw new HttpError(500);
}

$file = Filestore::upload(
    $_FILES['file']['tmp_name'],
    $_FILES['file']['name'],
    Context::arg('from'),
    []
);

echo json_encode([
    "success" => 1,
    "file" => [
        "url" => $file->url(),
        "name" => $file->filename(),
        "size" => $file->bytes(),
        "uuid" => $file->uuid()
    ]
]);
