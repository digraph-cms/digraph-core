<?php

namespace DigraphCMS\UI\DataTables;

use DigraphCMS\Users\User;
use DigraphCMS\Users\UserSelect;

class UserTable extends UserTable
{
    public function __construct(UserSelect $select)
    {
        parent::__construct(
            $select,
            [$this, 'callback'],
            [
                new QueryColumnHeader('Name', 'user_name', $select),
                new QueryColumnHeader('Date registered', 'created', $select),
                new ColumnHeader('Groups')
            ]
        );
    }

    public function callback(User $user): array
    {
        return [
            $user,
            $user->created()->format('Y-m-d'),
            implode(', ', $user->groups())
        ];
    }
}
