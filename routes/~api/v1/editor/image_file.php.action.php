<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;

if (Context::arg('csrf') !== Cookies::csrfToken('editor')) {
    throw new HttpError(401);
}

Context::response()->private(true);
Context::response()->filename('response.json');

$file = Filestore::upload(
    $_FILES['image']['tmp_name'],
    $_FILES['image']['name'],
    Context::arg('from'),
    []
);

echo json_encode([
    "success" => 1,
    "file" => [
        "url" => (new URL('/~api/v1/editor/image_preview.php?image=' . $file->uuid()))->__toString(),
        "uuid" => $file->uuid()
    ]
]);
