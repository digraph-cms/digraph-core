<?php

use DigraphCMS\Content\Slugs;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;

echo '<div class="navigation-frame" id="page-urls-form">';

// display table
$table = new PaginatedTable(
    Slugs::list(Context::page()->uuid()),
    function (string $slug) {
        $button = new SingleButton(
            'Remove',
            function () use ($slug) {
                Slugs::delete(Context::page()->uuid(), $slug);
            },
            ['button--warning']
        );
        return [
            $slug,
            $button
        ];
    },
    [
        new ColumnStringFilteringHeader('URL path', 'slug'),
        new ColumnHeader('Remove URL')
    ]
);
$table->paginator()->perPage(15);
echo $table;

// display form below table
$pattern = (new Field('Set new URL pattern'))
    ->setDefault(Context::page()->slugPattern())
    ->setRequired(true)
    ->addTip('Add a leading slash to make pattern relative to site root, otherwise it will be relative to the page\'s parent URL.');

$unique = (new CheckboxField('Force URL to be unique'))
    ->setDefault(Context::page()::DEFAULT_UNIQUE_SLUG)
    ->addTip('Check this box to force the generated URL to be unique. If it collides with an existing URL it will have a random ID appended to it.')
    ->addTip('Leave unchecked to allow it to collide with existing URLs. Disambiguation pages are served at any colliding URLs automatically if necessary.');


echo (new FormWrapper(Context::pageUUID() . '_urls'))
    ->addChild($pattern)
    ->addChild($unique)
    ->addCallback(function () use ($pattern, $unique) {
        try {
            // set new slug from pattern
            Slugs::setFromPattern(
                Context::page(),
                $pattern->value(),
                $unique->value()
            );
            // save pattern into page
            $page = Context::page();
            $page->slugPattern($pattern->value());
            $page->update();
            Notifications::flashConfirmation('URL updated');
        } catch (\Throwable $th) {
            Notifications::flashError($th->getMessage());
        }
        // refresh page
        throw new RefreshException();
    });

echo '</div>';
