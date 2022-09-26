<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Cron\Cron;
use DigraphCMS\HTTP\HttpError;

// route or poor man's cron must be enabled
if (!(Config::get('cron.route_enabled') || Config::get('cron.poor_mans_cron'))) {
    throw new HttpError(403, "Cron route is disabled by configuration.");
}

// cached output
$count = Cache::get(
    'cron/route',
    function () {
        // set up time limit
        $timeLimit = Config::get('cron.time_limit') ?? 0;
        $endBy = $timeLimit ? time() + $timeLimit : null;
        set_time_limit(max(10, ceil($timeLimit * 1.5)));
        // run jobs
        return Cron::runJobs($endBy);
    },
    60
);

// browser-side output
Context::response()->browserTTL(120);
Context::response()->filename('cron.js');
echo "// script does nothing in client" . PHP_EOL;
echo "// jobs run in last execution: $count";
