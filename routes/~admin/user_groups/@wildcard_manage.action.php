<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\UserInput;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

$group = Users::group(Context::url()->actionSuffix());
if (!$group) throw new HttpError(404);

printf('<h1>Manage group %s</h1>', $group->name());

echo '<div class="navigation-frame navigation-frame--stateless" id="group-management-interface">';

$form = (new FormWrapper)
    ->setData('target', '_frame')
    ->addClass('inline-form');
$form->button()->setText('Add user');
$user = (new UserInput(''))
    ->setRequired(true);
$form->addChild($user);
if ($form->ready()) {
    if (Permissions::inGroup($group->uuid(), Users::get($user->value()))) {
        Notifications::flashWarning('User is already in group');
        throw new RefreshException();
    }
    try {
        DB::query()
            ->insertInto(
                'user_group_membership',
                [
                    'user_uuid' => $user->value(),
                    'group_uuid' => $group->uuid()
                ]
            )->execute();
        throw new RefreshException();
    } catch (\Exception $th) {
        if ($th instanceof RefreshException) throw $th;
        Notifications::error('Error: ' . $th->getMessage());
    }
}
echo $form;

echo new PaginatedTable(
    Users::groupMembers($group->uuid()),
    function (User $user) use ($group): array {
        $allowRemove = !in_array($user->uuid(), ['system']);
        // if ($group->uuid() == 'admins' && Permissions::inGroup($group->uuid(), $user)) $allowRemove = false;
        return [
            $user,
            !$allowRemove ? '' : (new CallbackLink(
                function () use ($group, $user) {
                    DB::query()
                        ->deleteFrom('user_group_membership')
                        ->where('user_uuid', $user->uuid())
                        ->where('group_uuid', $group->uuid())
                        ->execute();
                },
                null,
                null,
                '_frame'
            ))->addChild('remove')
                ->setID(md5('remove-' . $user->uuid()))
        ];
    }
);

echo '</div>';
