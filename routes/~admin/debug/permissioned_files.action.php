<h1>Permissioned files</h1>
<p>
    The following media files should only be visible to certain users.
</p>
<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;

if ($file = Filestore::get('debug_users_only')) {
    $file->delete();
}
$file = Filestore::create(
    'This file is only visible to signed-in users',
    'users-only.txt',
    'debug',
    [],
    'debug_users_only',
    fn(FilestoreFile $file, User $user) => Permissions::inGroup('users', $user),
);

printf(
    '<p><a href="%s">%s</a></p>',
    $file->url(),
    $file->filename()
);