<?php

use DigraphCMS\Content\Download;
use DigraphCMS\Context;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\UI\Notifications;

$page = Context::page();
assert($page instanceof Download);

if ($page->immediateDownload()) {
    if ($page->isEditor()) {
        Notifications::printWarning(
            'This page will immediately download the file for others. That is disabled for you for editing purposes.'
        );
    } else {
        throw new ArbitraryRedirectException($page->download()->url());
    }
}

echo $page->richContent('body');