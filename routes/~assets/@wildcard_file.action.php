<h1>Permissioned file</h1>
<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\Context;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Media\PermissionedFile;

Context::response()->private(true);

// get identifier from URL
/** @var string */
$identifier = Context::url()->actionSuffix();

// get filename from cache
$filename = Cache::get('media/permissioned_file/filename_' . $identifier);
if (!$filename) throw new HttpError(404);

// get permissions from cache
$permissions = Cache::get('media/permissioned_file/permissions_' . $identifier);
if (!$permissions) throw new HttpError(404);

// check permissions
if (!$permissions()) throw new AccessDeniedError('File access denied');

// pass through file
Context::response()
    ->setContentFile(
        PermissionedFile::buildPath($identifier, $filename)
    );