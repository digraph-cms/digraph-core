<h1>Permissioned files</h1>
<p>
    The following media files should only be visible to certain users.
</p>
<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\FS;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;

if ($filestore = Filestore::get('debug_users_only')) {
    $filestore->delete();
}
$filestore = Filestore::create(
    'This filestore file is only visible to signed-in users',
    'users-only-filestore.txt',
    'debug',
    [],
    'debug_users_only',
    fn(User $user) => Permissions::inGroup('users', $user),
);

$media = new DeferredFile(
    'users-only-media.txt',
    function (DeferredFile $file) {
        FS::dump($file->path(), 'This media file is only visible to signed-in users');
    },
    'debug_users_only',
    null,
    fn(User $user) => Permissions::inGroup('users', $user)
);

printf(
    '<p><a href="%s">%s</a></p>',
    $filestore->url(),
    $filestore->filename()
);

printf(
    '<p><a href="%s">%s</a></p>',
    $media->url(),
    $media->filename()
);