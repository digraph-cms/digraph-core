<h1>All page slugs</h1>
<p>
    This page lists all page URL slugs in the site.
    This can be useful for looking up whether a given URL goes anywhere.
    Note that if you delete a slug here that matches a page's current slug pattern, it
    may be recreated later during periodic maintenance background jobs.
</p>
<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\DB\DB;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnBooleanFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnPageFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Toolbars\ToolbarLink;

$table = new PaginatedTable(
    DB::query()
        ->from('page_slug')
        ->orderBy('updated DESC'),
    function (array $row): array {
        return [
            (
            new ToolbarLink(
                'Delete',
                'delete',
                DB::query()->deleteFrom('page_slug', $row['id'])->execute(...)
            )
            )->setData('target', '_frame'),
            $row['url'],
            Pages::get($row['page_uuid'])->url()->html(),
            $row['archive'] ? 'Y' : '',
            $row['expires'] ? Format::date($row['expires']) : 'never',
            Format::date($row['updated'])
        ];
    },
    [
        '',
        new ColumnStringFilteringHeader('Slug', 'url'),
        new ColumnPageFilteringHeader('Page', 'page_uuid'),
        new ColumnBooleanFilteringHeader('Archive', 'archive'),
        new ColumnDateFilteringHeader('Expires', 'expires',),
        new ColumnDateFilteringHeader('Updated', 'updated')
    ]
);

echo $table;
