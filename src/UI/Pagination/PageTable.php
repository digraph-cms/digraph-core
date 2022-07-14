<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\Content\AbstractPage;

class PageTable extends PaginatedTable
{
    public function __construct($select)
    {
        parent::__construct(
            $select,
            function (AbstractPage $page): array {
                return [
                    $page->url()->html(),
                    $page->created()->format('Y-m-d'),
                    $page->updated()->format('Y-m-d'),
                    $page->createdBy(),
                    $page->updatedBy()
                ];
            },
            [
                new ColumnSortingHeader('Name', 'name', $select),
                new ColumnSortingHeader('Created', 'created', $select),
                new ColumnSortingHeader('Modified', 'updated', $select),
                new ColumnHeader('Created by'),
                new ColumnHeader('Modified by')
            ]
        );
    }
}
