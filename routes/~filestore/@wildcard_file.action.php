<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Security\Security;

$identifier = Context::url()->actionSuffix();
$file = Filestore::get($identifier);

if (!$file) throw new HttpError(404);

// check if there are permissions and enforce them if found
if ($p = $file->permissions()) {
    Security::requireSecurityCheck();
    Context::response()->private(true);
    $allowed = $file->checkPermissions();
    if (!$allowed) throw new AccessDeniedError('File access denied');
}

// pass through file
Context::response()
    ->template('null.php');
Context::response()
    ->setContentFile($file->path());
Context::response()
    ->filename($file->filename());
