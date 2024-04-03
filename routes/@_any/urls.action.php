<?php

use DigraphCMS\Content\Slugs;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;

echo '<div class="navigation-frame" id="page-urls-form">';

// display table
$table = new PaginatedTable(
    DB::query()
        ->from('page_slug')
        ->where('page_uuid = ?', [Context::pageUUID()])
        ->orderBy('id DESC')
        ->fetchAll(),
    function (array $row) {
        $button = (new CallbackLink(
            function () use ($row) {
                Slugs::delete(Context::pageUUID(), $row['url']);
            }
        ))
            ->addChild('Remove')
            ->addClass('button button--warning');
        return [
            $row['url'],
            $row['expires'] ? Format::date($row['expires']) : 'never',
            Format::date($row['updated']),
            $button
        ];
    },
    [
        new ColumnStringFilteringHeader('URL path', 'slug'),
        new ColumnDateFilteringHeader('Expires', 'expires'),
        new ColumnDateFilteringHeader('Updated', 'updated'),
        ''
    ]
);
$table->paginator()->perPage(15);
echo $table;

// display form below table
$pattern = (new Field('Set new URL/pattern'))
    ->setDefault(Context::page()->slugPattern())
    ->setRequired(true)
    ->addTip('Add a leading slash to make pattern relative to site root, otherwise it will be relative to the page\'s parent URL.');

$save = (new CheckboxField('Save pattern'))
    ->addTip('Check this box to save the above URL/pattern as the default pattern, and make it the new primary URL.')
    ->addTip('If unchecked, the primary URL pattern will be kept the same, but may be updated if necessary.');

$unique = (new CheckboxField('Force URL to be unique'))
    ->setDefault(Context::page()::DEFAULT_UNIQUE_SLUG)
    ->addTip('Check this box to force the generated URL to be unique. If it collides with an existing URL it will have a random ID appended to it.')
    ->addTip('Leave unchecked to allow it to collide with existing URLs. Disambiguation pages are served at any colliding URLs automatically if necessary.');

$expires = (new DatetimeField('Expires'))
    ->setDefault(Context::page()->slugDefaultExpiration() ? Format::parseDate(Context::page()->slugDefaultExpiration()) : null)
    ->setRequired(false)
    ->addTip('Set an expiration date for this URL.')
    ->addTip('Leave blank for no expiration.');

if (Context::page()->slugDefaultExpiration()) {
    $expires->addTip('Note that if this URL matches whatever the saved pattern generates, it will be automatically regenerated later &emdash; including an expiration date.');
}

echo (new FormWrapper(Context::pageUUID() . '_urls'))
    ->addChild($pattern)
    ->addChild($expires)
    ->addChild($save)
    ->addChild($unique)
    ->addCallback(function () use ($pattern, $save, $unique, $expires) {
        try {
            // set new slug from pattern
            Slugs::setFromPattern(
                Context::page(),
                $pattern->value(),
                $unique->value(),
                $expires->value() ? $expires->value()->getTimeStamp() : false,
            );
            $page = Context::page();
            // save pattern into page
            if ($save->value()) {
                $page->slugPattern($pattern->value());
            }
            // otherwise set pattern again from saved pattern so it stays at the top
            else {
                Slugs::setFromPattern(
                    Context::page(),
                    $page->slugPattern(),
                    $unique->value()
                );
            }
            $page->update();
            Notifications::flashConfirmation('URL updated');
        } catch (\Throwable $th) {
            if ($th instanceof Exception) {
                Notifications::flashError($th->getMessage());
            } else {
                Notifications::flashError(get_class($th));
            }
        }
        // refresh page
        throw new RefreshException();
    });

echo '</div>';
