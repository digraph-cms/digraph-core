<?php

namespace DigraphCMS\UI\DataTables;

use DigraphCMS\Content\Page;
use DigraphCMS\Content\PageSelect;

class PageTable extends QueryTable
{
    public function __construct(PageSelect $select)
    {
        parent::__construct(
            $select,
            [$this, 'callback'],
            [
                new QueryColumnHeader('Name', 'name', $select),
                new QueryColumnHeader('Created', 'created', $select),
                new QueryColumnHeader('Modified', 'updated', $select),
                new ColumnHeader('Created by'),
                new ColumnHeader('Modified by')
            ]
        );
    }

    public function callback(Page $page): array
    {
        return [
            $page->url()->html(),
            $page->created()->format('Y-m-d'),
            $page->updated()->format('Y-m-d'),
            $page->createdBy(),
            $page->updatedBy()
        ];
    }
}
