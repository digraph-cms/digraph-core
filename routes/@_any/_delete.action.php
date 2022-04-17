<h1>Deleting page</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;

$page = Context::page();
if (Context::arg('csrf') != Cookies::csrfToken('delete_' . Context::pageUUID())) throw new HttpError(400);

$job = $page->recursiveDelete();

$bar = new DeferredProgressBar($job->group());
$bar->setDisplayAfter('Successfully deleted page and everything under it');
echo $bar;
