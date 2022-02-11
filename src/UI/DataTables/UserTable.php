<?php

namespace DigraphCMS\UI\DataTables;

use DigraphCMS\Users\User;
use DigraphCMS\Users\UserSelect;

class UserTable extends QueryTable
{
    public function __construct(UserSelect $select)
    {
        parent::__construct(
            $select,
            [$this, 'callback'],
            [
                new QueryColumnHeader('User', 'name', $select),
                new QueryColumnHeader('Registered', 'created', $select),
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
