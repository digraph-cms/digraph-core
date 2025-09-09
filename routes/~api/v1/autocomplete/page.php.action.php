<?php

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;

if (Context::arg_string('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(401);
}

Context::response()->private(true);
Context::response()->filename('response.json');

// get exact results
$pages = Pages::getAll(Context::arg_string('query', true));
// filter by class
if ($class = Context::arg_string('class', true)) {
    $pages = array_filter(
        $pages,
        function (AbstractPage $page) use ($class) {
            return $page->class() == $class;
        }
    );
}
// get stricter name matches
if (count($pages) < 100) {
    $query = Pages::select()->limit(100);
    foreach (preg_split('/ +/', Context::arg_string('query', true)) as $word) {
        $query->like('name', $word);
    }
    if ($class = Context::arg_string('class')) {
        $query->where('class', $class);
    }
    $pages = array_merge(
        $pages,
        $query->fetchAll()
    );
}
// get looser name matches
if (count($pages) < 100) {
    $query = Pages::select()->limit(100);
    foreach (preg_split('/ +/', Context::arg_string('query', true)) as $word) {
        $query->like('name', $word, true, true, 'OR');
    }
    if ($class = Context::arg_string('class')) {
        $query->where('class', $class);
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
            Dispatcher::firstValue('onScorePageResult', [$page, Context::arg_string('query', true)])
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
            return Dispatcher::firstValue('onPageAutocompleteCard', [$page, Context::arg_string('query', true)]);
        },
        array_slice($pages, 0, 50)
    )
);
