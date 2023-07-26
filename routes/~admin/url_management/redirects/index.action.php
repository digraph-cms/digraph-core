<h1>URL redirects</h1>
<p>
    URL redirection checks run before every single other step of building a page.
    This means that a redirect created here will override <strong>all other content sources</strong>.
    This means that if you create a redirect that overrides a vital admin page or
    API endpoint you can break your site.
</p>
<p>
    There is an automatic check that will prevent you from breaking this management
    tool though, so you will always be able to undo and recover using this URL.
</p>
<?php
use DigraphCMS\DB\DB;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnUserFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\URL\Redirects;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

$redirects = DB::query()
    ->from('redirect')
    ->order('created DESC');

echo '<div class="navigation-frame navigation-frame--stateless" id="url-redirect-interface">';
printf(
    '<p><a href="%s" class="button button--inverted" data-target="_frame">Add redirect</a></p>',
    new URL('_add_redirect.html')
);

$table = new PaginatedTable(
    $redirects,
    function (array $row): array {
        return [
            (
                new ToolbarLink(
                'Delete',
                'delete',
                fn() => Redirects::delete($row['redirect_from']),
                )
            )->setData('target', '_frame'),
            $row['redirect_from'],
            $row['redirect_to'],
            Format::date($row['created']),
            Users::user($row['created_by']),
        ];
    },
    [
        '',
        new ColumnStringFilteringHeader('From', 'redirect_from'),
        new ColumnStringFilteringHeader('To', 'redirect_to'),
        new ColumnDateFilteringHeader('Created', 'created'),
        new ColumnUserFilteringHeader('Created by', 'created_by')
    ]
);

echo $table;

echo '</div>';