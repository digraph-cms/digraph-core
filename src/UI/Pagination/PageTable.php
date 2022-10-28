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
                new ColumnStringFilteringHeader('Name', 'page.name'),
                new ColumnDateFilteringHeader('Created', 'page.created'),
                new ColumnDateFilteringHeader('Modified', 'page.updated'),
                new ColumnUserFilteringHeader('Created by', 'page.created_by'),
                new ColumnUserFilteringHeader('Modified by', 'page.updated_by')
            ]
        );
    }
}
