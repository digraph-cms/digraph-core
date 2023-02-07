<?php

use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Search\Search;
use DigraphCMS\Search\SearchForm;
use DigraphCMS\Search\SearchResult;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedSection;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;

$query = trim(Context::arg('q') ?? '');
if (!$query) {
    echo '<h1>Site search</h1>';
    echo new SearchForm();
    return;
}

echo '<h1>Search results</h1>';
Breadcrumb::setTopName('Search results');
Breadcrumb::parent(new URL('/~search/'));
echo new SearchForm();

ob_start();
Dispatcher::dispatchEvent('onSearchHighlightSection', [$query]);
$highlighted = trim(ob_get_clean());
if ($highlighted) {
    echo '<div class="card card--light card--search-results-highlight">';
    echo $highlighted;
    echo '</div>';
}

$query = Search::query($query);

$list = new PaginatedSection(
    $query,
    function (SearchResult $result) {
        return Templates::render('search/result.php', ['result' => $result]);
    }
);

try {
    if (!$query->count()) {
        Notifications::printWarning('No results');
        return;
    }
    echo '<div class="search-results">';
    Dispatcher::dispatchEvent('onDisplaySearchResults', [$query]);
    echo $list;
    echo '</div>';
} catch (\Throwable $th) {
    Notifications::printError('Error generating search results');
}
