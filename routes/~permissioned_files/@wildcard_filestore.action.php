<h1>Permissioned file</h1>
<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Security\Security;
use DigraphCMS\Users\Permissions;

Security::requireSecurityCheck();

// get identifier from URL
/** @var string */
$uuid = Context::url()->actionSuffix();
$filestore = Filestore::get($uuid);

// check object exists
if (!$filestore) throw new HttpError(404);

// set to private if exists
Context::response()->private(true);

// check permissions with object
if (!Permissions::inMetaGroup('content__admin') && !$filestore->checkPermissions()) throw new AccessDeniedError('File access denied');

// pass through file
Context::response()
    ->template('null.php');
Context::response()
    ->setContentFile($filestore->path());
Context::response()
    ->filename($filestore->filename());