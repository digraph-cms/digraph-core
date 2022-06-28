<h1>Site search</h1>
<?php

use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Search\Search;
use DigraphCMS\Search\SearchForm;
use DigraphCMS\Search\SearchResult;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

$form = new SearchForm(true);
echo $form;

if ($form->queryMode() == 'boolean') Notifications::printNotice(
    sprintf(
        '<a href="%s">Boolean search syntax reference</a>',
        new URL('_boolean_reference.html')
    )
);

if (!$form->query()) return;

ob_start();
Dispatcher::dispatchEvent('onSearchHighlightSection', [$form->query(), $form->queryMode()]);
$highlighted = trim(ob_get_clean());
if ($highlighted) {
    echo '<div class="card card--light card--search-results-highlight">';
    echo $highlighted;
    echo '</div>';
}

$query = Search::query($form->query(), $form->queryMode());

$table = new QueryTable(
    $query,
    function (SearchResult $result): array {
        return [
            implode(PHP_EOL, [
                sprintf('<div class="search-results__title"><a href="%s">%s<a></div>', $result->url(), $result->title()),
                sprintf('<div class="search-results__body">%s</div>', $result->body()),
                sprintf('<div class="search-results__url">%s</div>', $result->url()),
            ])
        ];
    },
    []
);

try {
    if (!$query->count()) {
        Notifications::printWarning('No results');
        return;
    }
    echo '<div class="search-results">';
    Dispatcher::dispatchEvent('onDisplaySearchResults', [$form->query(), $form->queryMode()]);
    echo $table;
    echo '</div>';
} catch (\Throwable $th) {
    Notifications::printError('Error generating search results');
}
