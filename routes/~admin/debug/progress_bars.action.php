<h1>Progress bar development</h1>

<?php

use DigraphCMS\Cron\Deferred;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\Cron\DeferredProgressBar;

$job = new DeferredJob(
    function (DeferredJob $job) {
        $num = random_int(1, 10);
        for ($i = 0; $i < $num; $i++) {
            if (Deferred::groupCount($job->group()) >= 1000) return 'All setup done';
            $job->spawnClone();
        }
        return 'Ran a piece';
    }
);
$job->insert();

echo new DeferredProgressBar($job->group());
