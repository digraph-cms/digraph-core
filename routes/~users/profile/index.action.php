<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\TabInterface;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\Users;

$user = Users::get(Context::arg('id')) ?? Users::current();
if (!$user) throw new HttpError(404);

echo "<h1>User profile: " . $user->name() . "</h1>";

$tabs = Router::search('~users/profile/@tabs/*.php');
usort($tabs, function ($a, $b) {
    return strncasecmp(basename($a), basename($b), 250);
});

$tabInterface = new TabInterface('profile');

foreach ($tabs as $file) {
    ob_start();
    include $file;
    $contents = ob_get_clean();
    if (!$contents) continue;
    $tabInterface->addTab(
        URLify::filter(
            URLs::pathToName(basename($file)),
            removeWords: true,
            strToLower: true,
            separator: '_'
        ),
        URLs::pathToName(basename($file)),
        function () use ($contents) {
            echo $contents;
        }
    );
}

echo $tabInterface;