<h1>Scheduled jobs test</h1>
<p>
    This page attempts to create a scheduled job that runs hourly for the next 24 hours, and provides a quick link that -?setExpiresallows you to view their recent and next-scheduled runs.
</p>
<?php
use DigraphCMS\Cron\DailySchedule;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\Cron\ScheduledJobs;
use DigraphCMS\URL\URL;

$group = ScheduledJobs::schedule(
    function (DeferredJob $job) {
        $group = $job->group();
        $job->spawn(fn() => "Spawned from hourly debug job: " . $group);
        return "Ran hourly job";
    },
    (new DailySchedule(range(0, 23)))
        ->setExpires(time() + 86400),
    'debug/hourly'
);

$link = new URL('/admin/background_processes/deferred_execution/_inspect_group.html?id=' . $group);
echo $link->html();