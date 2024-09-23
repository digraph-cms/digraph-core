<h1>Permissioned file</h1>
<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\Context;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Security\Security;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\Users;

Security::requireSecurityCheck();

// get identifier from URL
/** @var string */
$identifier = Context::url()->actionSuffix();
$info = Cache::get('permissioned_media/info/' . $identifier);

// check that info exists
if (!$info) throw new HttpError(404);

// set to private if exists
Context::response()->private(true);

// check permissions
$allowed =
    Permissions::inMetaGroup('content__editor')
    || call_user_func(
        $info['permissions'],
        Users::current() ?? Users::guest(),
    );
if (!$allowed) throw new AccessDeniedError('File access denied');

// pass through file
Context::response()
    ->template('null.php');
Context::response()
    ->setContentFile($info['path']);
Context::response()
    ->filename($info['filename']);
