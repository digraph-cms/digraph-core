<?php

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;

if (Context::arg('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(401);
}

Context::response()->private(true);
Context::response()->filename('response.json');

// get exact results
$pages = Pages::getAll(Context::arg('query'));
// get stricter name matches
if (count($pages) < 100) {
    $query = Pages::select()->limit(100);
    foreach (preg_split('/ +/', Context::arg('query')) as $word) {
        $query->where('name like ?', ['%' . $word . '%']);
    }
    $pages = array_merge(
        $pages,
        $query->fetchAll()
    );
}
// get looser name matches
if (count($pages) < 100) {
    $query = Pages::select()->limit(100);
    foreach (preg_split('/ +/', Context::arg('query')) as $word) {
        $query->where('name like ?', ['%' . $word . '%'], 'OR');
    }
    $pages = array_merge(
        $pages,
        $query->fetchAll()
    );
}

// score results
$pages = array_map(
    function (AbstractPage $page) {
        return [
            $page,
            Dispatcher::firstValue('onScorePageResult', [$page, Context::arg('query')])
        ];
    },
    $pages
);
// sort by score
usort(
    $pages,
    function ($a, $b) {
        return $b[1] - $a[1];
    }
);
// strip back to just page object
$pages = array_map(
    function ($arr) {
        return $arr[0];
    },
    $pages
);

$pages = array_unique($pages, SORT_REGULAR);

echo json_encode(
    array_map(
        function (AbstractPage $page) {
            return Dispatcher::firstValue('onPageAutocompleteCard', [$page, Context::arg('query')]);
        },
        array_slice($pages, 0, 50)
    )
);
