<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

$group = Users::group(Context::url()->actionSuffix());
if (!$group) throw new HttpError(404);
if (in_array($group->uuid(), ['admins', 'editors'])) throw new HttpError(404);

printf('<h1>Delete group %s</h1>', $group->name());

echo new SingleButton(
    'Delete group now',
    function () use ($group) {
        DB::query()
            ->deleteFrom('user_group_membership')
            ->where('group_uuid', $group->uuid())
            ->execute();
        DB::query()
            ->deleteFrom('user_group')
            ->where('uuid', $group->uuid())
            ->execute();
        throw new RedirectException(new URL('./'));
    },
    ['button--danger']
);
