<h1>Permissioned files</h1>
<p>
    The following media files should only be visible to certain users.
</p>
<?php

use DigraphCMS\Media\PermissionedFile;
use DigraphCMS\Users\Permissions;

$users = (
    new PermissionedFile(
    'users-only.txt',
    'This file is only visible to signed-in users'
    )
)
    ->setPermissions(function () {
        return Permissions::inGroup('users');
    });
printf(
    '<p><a href="%s">%s</a></p>',
    $users->url(),
    $users->filename()
);