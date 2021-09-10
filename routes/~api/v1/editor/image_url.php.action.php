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

$json = file_get_contents('php://input');
$json = json_decode($json, true);

$data = file_get_contents($json['url']);
$tmp = tempnam(sys_get_temp_dir(), 'imgurl');
file_put_contents($tmp, $data);

$file = Filestore::upload(
    $tmp,
    basename($json['url']),
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
