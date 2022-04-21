<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;
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
        // set override user to system
        Session::overrideUser('system');
        // get jobs from Dispatcher, and convert them into DB jobs
        static::buildDispatcherJobs();
        // count number of jobs run
        $count = 0;
        // flip a coin to see if Deferred goes first
        if ($deferredFirst = random_int(0, 1)) $count += Deferred::runJobs(null, $endByTime);
        // proceed with jobs one at a time
        while ((!$endByTime || time() < $endByTime) && $job = static::getNextJob()) {
            // don't make more than one attempt per job
            static::$skip[] = $job->id();
            // execute job
            $count += $job->execute() ? 1 : 0;
        }
        // run Deferred jobs afterwards if it lost the coin toss
        if (!$deferredFirst) $count += Deferred::runJobs(null, $endByTime);
        // unset override user
        Session::overrideUser(null);
        // return number of jobs run
        return $count;
    }

    protected static function buildDispatcherJobs()
    {
        foreach (Config::get('cron.intervals') as $name => $interval) {
            $jobs = Dispatcher::getListeners('onCron_' . $name);
            foreach ($jobs as $fn) {
                new CronJob('Dispatcher', static::generateDispatcherJobName($fn), $fn, $name);
            }
        }
    }

    protected static function generateDispatcherJobName($fn)
    {
        // might be a straight string
        if (is_string($fn)) return $fn;
        // might be an array pair
        list($classOrObj, $method) = $fn;
        if (is_object($classOrObj)) {
            return sprintf(
                '%s->%s (%s)',
                get_class($classOrObj),
                $method,
                md5(\Opis\Closure\serialize($classOrObj))
            );
        }
        return implode('::', $fn);
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
