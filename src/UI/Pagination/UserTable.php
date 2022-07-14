<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\Users\User;
use DigraphCMS\Users\UserSelect;

class UserTable extends PaginatedTable
{
    public function __construct(UserSelect $select)
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
                new ColumnSortingHeader('User', 'name', $select),
                new ColumnSortingHeader('Registered', 'created', $select),
                new ColumnHeader('Groups')
            ]
        );
    }
}
