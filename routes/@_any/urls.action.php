<h1>Page URLs</h1>
<?php

use DigraphCMS\Content\Slugs;
use DigraphCMS\Context;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\DataTables\ArrayTable;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\Forms\Form;
use Formward\Fields\Checkbox;
use Formward\Fields\Input;

echo '<div class="navigation-frame" id="page-urls-form">';

// set up and handle form first, so that its changes appear in table immediately
$form = new Form('Update URL');
$form->addClass('compact');
$form['pattern'] = new Input('Pattern');
$form['pattern']->required(true);
$form['pattern']->default(Context::page()->slugPattern());
$form['save'] = new Checkbox('Update saved pattern');
$form['save']->addTip('Check this box to update the URL pattern saved in the page, so that the URL will update to match it with future edits.');
$form['save']->addTip('Leave unchecked if you\'re all right with the page\'s primary URL being reverted to the saved pattern next time it is updated.');
$form['save']->default(true);
$form['unique'] = new Checkbox('Make unique');
$form['unique']->addTip('Check this box to force the generated URL to be unique. If it collides with an existing URL it will have a random ID appended to it.');
$form['unique']->addTip('Leave unchecked to allow it to collide with existing URLs. Disambiguation pages are served at any colliding URLs automatically if necessary.');
$form['unique']->default(true);

if ($form->handle()) {
    Slugs::setFromPattern(
        Context::page(),
        $form['pattern']->value(),
        $form['unique']->value()
    );
    throw new RefreshException();
}

// display table
$table = new ArrayTable(
    Slugs::list(Context::page()->uuid()),
    function (int $i, string $slug) {
        $button = new SingleButton(
            'Remove',
            function () use ($slug) {
                Slugs::delete(Context::page()->uuid(), $slug);
            },
            ['warning']
        );
        return [
            $slug,
            $button
        ];
    },
    [
        new ColumnHeader('URL path'),
        new ColumnHeader('Remove URL')
    ]
);
echo $table;

// display form below table
echo $form;

echo '</div>';
