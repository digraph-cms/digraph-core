<?php

use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

if (Context::arg('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(401);
}

Permissions::requireMetaGroup('users__query');

Context::response()->private(true);
Context::response()->filename('response.json');

// get exact results
$users = Users::get(Context::arg('query'));
$users = $users ? [$users] : [];
// get stricter name matches
$query = Users::select()->limit(100);
foreach (preg_split('/ +/', Context::arg('query')) as $word) {
    $query->like('name', $word, true, true);
}
$users = array_merge(
    $users,
    $query->fetchAll()
);
// get looser name matches
if (count($users) < 100) {
    $query = Users::select()->limit(100);
    foreach (preg_split('/ +/', Context::arg('query')) as $word) {
        $query->like('name', $word, true, true, 'OR');
    }
    $users = array_merge(
        $users,
        $query->fetchAll()
    );
}

// make unique
$users = array_unique($users, SORT_REGULAR);

echo json_encode(
    array_map(
        function (User $user) {
            return Dispatcher::firstValue('onUserAutoCompleteCard', [$user, Context::arg('query')]);
        },
        array_slice($users, 0, 50)
    )
);
