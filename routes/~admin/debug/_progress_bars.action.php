<h1>Progress bar development</h1>

<?php

use DigraphCMS\Cron\Deferred;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\URL\URL;

$job = new DeferredJob(
    function (DeferredJob $job) {
        $num = random_int(1, 100);
        for ($i = 0; $i < $num; $i++) {
            if (Deferred::groupCount($job->group()) >= 500) return 'All setup done';
            $job->spawnClone();
        }
        return 'Ran a piece';
    }
);

$bar = new DeferredProgressBar($job->group());
$bar->setDisplayAfter('Successfully did a bunch of nothing');
$bar->setBounceAfter(new URL('./'));
echo $bar;
