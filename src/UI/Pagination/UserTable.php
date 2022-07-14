<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\Users\User;

class UserTable extends PaginatedTable
{
    public function __construct($select)
    {
        parent::__construct(
            $select,
            function (User $user): array {
                return [
                    $user,
                    $user->created()->format('Y-m-d'),
                    implode(', ', $user->groups())
                ];
            },
            [
                new QueryColumnHeader('User', 'name', $select),
                new QueryColumnHeader('Registered', 'created', $select),
                new ColumnHeader('Groups')
            ]
        );
    }
}
