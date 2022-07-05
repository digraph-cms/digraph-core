<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\URL\URL;

class Cron
{
    protected static $skip = [];

    public static function renderPoorMansCron()
    {
        // return nothing if poor man's cron is off
        if (!Config::get('cron.poor_mans_cron')) return null;
        // otherwise return output cached, to limit db queries
        return Cache::get(
            'cron/pmc',
            function () {
                // return nothing if there are no pending cron jobs, and also none exist whatsoever
                // this will allow cron jobs to be automatically built at first install
                // even if poor man's cron is in use
                $run = static::getNextJob()
                    || Deferred::getNextJob()
                    || DB::query()->from('cron')->limit(1)->count() == 0;
                if (!$run) return '';
                // render code
                return sprintf(
                    PHP_EOL . '<script>if (window.Worker) { new Worker("%s"); }</script>' . PHP_EOL,
                    new URL('/~cron/')
                );
            },
            60
        );
    }

    public static function exists(string $parent, string $name): bool
    {
        return !!DB::query()->from('delex')
            ->where('parent = ? AND name = ?', [$parent, $name])
            ->limit(1)
            ->count();
    }

    public static function runJobs(int $endByTime = null): int
    {
        // count number of jobs run
        $count = 0;
        // proceed with jobs one at a time
        while ((!$endByTime || time() < $endByTime) && $job = static::getNextJob()) {
            // don't make more than one attempt per job
            static::$skip[] = $job->id();
            // execute job
            if ($job->execute()) $count++;
        }
        // run Deferred jobs afterwards
        $count += Deferred::runJobs(null, $endByTime);
        // return number of jobs run
        return $count;
    }

    protected static function getNextJob(): ?CronJob
    {
        $query = DB::query()->from('cron')
            ->where('run_next <= ?', [time()])
            ->order('run_next ASC, id ASC')
            ->limit(1);
        if (static::$skip) {
            $query->where('id NOT IN (?)', [static::$skip]);
        }
        if (@$query->execute() && $result = $query->getResult()) {
            if ($result = $result->fetchObject(CronJob::class)) {
                return $result;
            }
        }
        return null;
    }
}
