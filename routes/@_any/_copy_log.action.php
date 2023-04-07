<?php

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\Cron\Deferred;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\Users\Users;

$page = Context::page();

echo "<h1>Copy log</h1>";

// display copy progress if available
$job = Context::page()['page_copy_log.job'];
if ($job && Deferred::groupCount($job)) {
    echo '<h2>Copy progress</h2>';
    echo new DeferredProgressBar($job);
}

// display main copy log
echo new PaginatedTable(buildPageCopyLog(Context::page()), null, ['Copied from', 'Date', 'User']);

// function for building log
if (function_exists('buildPageCopyLog')) return;
function buildPageCopyLog(AbstractPage $page)
{
    $status = $page['page_copy_log'];
    if (!$status) return [];
    $originalPage = Pages::get($page['page_copy_log.from']);
    $original = $originalPage ? $originalPage->url()->html() : '<strong>[not found]</strong>';
    $user = Users::get($page['page_copy_log.user']) ?? '<strong>[not found]</strong>';
    $time = $page['page_copy_log.time'] ? Format::datetime($page['page_copy_log.time']) : '<strong>[not found]</strong>';
    // recurse for original page
    if ($originalPage) $log = buildPageCopyLog($originalPage);
    else $log = [];
    // unshift this row onto array
    array_unshift($log, [
        $original,
        $time,
        $user
    ]);
    return $log;
}
