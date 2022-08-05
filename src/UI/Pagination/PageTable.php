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
                new ColumnStringFilteringHeader('Name', 'name'),
                new ColumnDateFilteringHeader('Created', 'created'),
                new ColumnDateFilteringHeader('Modified', 'updated'),
                new ColumnUserFilteringHeader('Created by', 'created_by'),
                new ColumnUserFilteringHeader('Modified by', 'updated_by')
            ]
        );
    }
}
