<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Cron\Cron;
use DigraphCMS\Cron\DelayedExecutor;

Context::response()->template('framed.php');

$timeLimit = Config::get('cron.time_limit') ?? 0;
$endBy = $timeLimit ? time() + $timeLimit : null;
set_time_limit(ceil($timeLimit * 1.5));

// flip a coin to decide whether Cron or DelayedExecutor gets to go first
if (rand(0, 1)) {
    Cron::run($endBy);
    DelayedExecutor::run($endBy);
} else {
    DelayedExecutor::run($endBy);
    Cron::run($endBy);
}
