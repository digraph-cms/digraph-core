<h1>Permissioned file</h1>
<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;

Context::response()->private(true);

// get identifier from URL
/** @var string */
$uuid = Context::url()->actionSuffix();
$file = Filestore::get($uuid);

// check permissions with object
if (!$file) throw new HttpError(404);
if (!$file->checkPermissions()) throw new AccessDeniedError('File access denied');

// pass through file
Context::response()
    ->setContentFile($file->path());
Context::response()
    ->filename($file->filename());