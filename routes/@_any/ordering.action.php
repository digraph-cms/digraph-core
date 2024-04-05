<p>
    Use this table to control the ordering of sub-pages.
    Useful for ordering automatically-generated tables of contents, or navbars based on a page, such as the homepage.
</p>
<?php

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Graph;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;

if (Context::page()::ORDER_IGNORES_WEIGHT) {
    Notifications::printWarning("This page does not use the weight column displayed here to sort its child pages.");
}

if (!Context::page()::ORDER_USES_SORT_NAME) {
    Notifications::printWarning("This page does not use the sort name column displayed here to sort its child pages.");
}

$query = Graph::children(Context::pageUUID());

$table = new PaginatedTable(
    $query,
    function (AbstractPage $page): array {
        // set up name form
        $name = (new FormWrapper("name_" . $page->uuid()))
            ->addClass('inline-autoform')
            ->addClass('navigation-frame navigation-frame--stateless');
        $name->button()->setText('Save');
        $nameField = new INPUT();
        $nameField->setDefault($page->sortName());
        $nameField->setAttribute('placeholder', $page->name(null, true));
        $name->addChild($nameField);
        $name->addCallback(function () use ($page, $nameField) {
            $page->setSortName(
                $nameField->value()
                    ? $nameField->value()
                    : null
            );
            $page->update();
            throw new RefreshException();
        });
        // set up weight form
        $weight = (new FormWrapper("weight_" . $page->uuid()))
            ->addClass('inline-autoform')
            ->addClass('navigation-frame navigation-frame--stateless');
        $weightField = new SELECT([
            -100 => 'Sticky',
            0 => 'Normal',
            100 => 'Heavy'
        ]);
        $weightField->setDefault($page->sortWeight())
            ->addClass('select--autosubmit');
        $weight->addChild($weightField);
        $weight->addCallback(function () use ($page, $weightField) {
            $page->setSortWeight($weightField->value());
            $page->update();
            throw new RefreshException();
        });
        $weight->button()->setText('Save');
        // return row
        return [
            $page->url()->html(),
            $name,
            $weight
        ];
    },
    [
        'Page',
        'Sort name',
        'Weight'
    ]
);

echo "<div class='navigation-frame' id='ordering-table'>";
printf('<a class="button" href="%s" data-target="ordering-table">Refresh table display order</a>', Context::url());
echo $table;
echo "</div>";

?>
<script>

</script>