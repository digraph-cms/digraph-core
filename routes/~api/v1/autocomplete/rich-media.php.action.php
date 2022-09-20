<?php

use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Users\Permissions;

Permissions::requireMetaGroup('content__edit');
if (Context::arg('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(401);
}

Context::response()->private(true);
Context::response()->filename('response.json');

// if there is no query, return 10 latest medias
if (!Context::arg('query')) {
    $pages = RichMedia::select()
        ->order('updated desc')
        ->limit(10)
        ->fetch();
}
$pages = [];
// exact name/id matches
$query = RichMedia::select();
$query->order('updated desc');
if ($phrase = trim(Context::arg('query'))) {
    $query->whereOr('name = ?', $phrase);
    $query->whereOr('uuid = ?', $phrase);
}
$pages = $pages + $query->fetchAll();
// exact internal matches
$query = RichMedia::select();
$query->order('updated desc');
if ($phrase = trim(Context::arg('query'))) {
    $query->like('name', $phrase, true, true, 'OR');
    $query->like('uuid', $phrase, true, true, 'OR');
    $query->like('data', $phrase, true, true, 'OR');
}
$query->limit(20);
$pages = $pages + $query->fetchAll();
// fuzzier matches
$query = RichMedia::select();
$query->order('updated desc');
foreach (explode(' ', Context::arg('query')) as $word) {
    $word = strtolower(trim($word));
    if ($word) {
        $query->whereOr('class = ?', $word);
        $query->like('name', $word, true, true, 'OR');
        $query->like('data', $word, true, true, 'OR');
    }
}
$query->limit(10);
$pages = $pages + $query->fetchAll();

echo json_encode(
    array_map(
        function (AbstractRichMedia $media) {
            return Dispatcher::firstValue('onRichMediaAutocompleteCard', [$media, Context::arg('query')]);
        },
        $pages
    )
);
