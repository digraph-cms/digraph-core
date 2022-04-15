<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\Deferred;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\HTTP\HttpError;

$group = Context::arg('group');
if (!$group || !Deferred::groupCount($group)) throw new HttpError(400);

echo new DeferredProgressBar($group);
