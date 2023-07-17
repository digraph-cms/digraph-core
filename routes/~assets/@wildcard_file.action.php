<h1>Permissioned file</h1>
<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\Context;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;

Context::response()->private(true);

// get identifier from URL
/** @var string */
$identifier = Context::url()->actionSuffix();

// get filename from cache
$filename = Cache::get('media/permissioned_files/filename/' . $identifier);
if (!$filename) throw new HttpError(404);

// get path from cache
$path = Cache::get('media/permissioned_files/path/' . $identifier);
if (!$path) throw new HttpError(404);

// get permissions from cache
$permissions = Cache::get('media/permissioned_files/permissions/' . $identifier);
if (!$permissions) throw new HttpError(404);

// check permissions
if (!$permissions()) throw new AccessDeniedError('File access denied');

// pass through file
Context::response()
    ->setContentFile($path);
Context::response()
    ->filename($filename);