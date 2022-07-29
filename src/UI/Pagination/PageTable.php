<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\PageSelect;

class PageTable extends PaginatedTable
{
    public function __construct(PageSelect $select)
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
                new ColumnSortingHeader('Name', 'name'),
                new ColumnSortingHeader('Created', 'created'),
                new ColumnSortingHeader('Modified', 'updated'),
                new ColumnHeader('Created by'),
                new ColumnHeader('Modified by')
            ]
        );
    }
}
