<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\FS;
use DigraphCMS\URL\URL;

class Cron
{
    protected static $skip = [];
    protected static $locks = [];

    public static function renderPoorMansCron()
    {
        // return nothing if poor man's cron is off
        if (!Config::get('cron.poor_mans_cron')) return null;
        // otherwise return output cached, to limit db queries
        return Cache::get(
            'cron/pmc',
            function () {
                // return nothing if there are no cron jobs
                if (!static::getNextJob()) return null;
                // render code
                return sprintf(
                    '<script>if (window.Worker) { new Worker("%s"); }</script>',
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
        // get jobs from Dispatcher, and convert them into DB jobs
        static::buildDispatcherJobs();
        // count number of jobs run
        $count = 0;
        // proceed with jobs one at a time
        while ($job = static::getNextJob()) {
            // don't make more than one attempt per job
            static::$skip[] = $job->id();
            // try to get lock
            if (!static::getLock($job)) continue;
            // try to execute a single job, if a lock was obtained for it
            $count++;
            try {
                // run job, runner should handle logging its own success
                $job->run();
            } catch (\Throwable $th) {
                // record exception in table
                DB::query()->update(
                    'cron',
                    [
                        'error_time' => time(),
                        'error_message' => get_class($th) . ': ' . $th->getMessage()
                    ],
                    $job->id()
                )->execute();
            }
            // release lock
            static::releaseLock($job);
            // break loop if time limit has been reached
            if ($endByTime && time() >= $endByTime) break;
        }
        // return number of jobs run
        return $count;
    }

    protected static function getLock(CronJob $job): bool
    {
        if ($job->id() === null) return false;
        // make sure necessary lock files directory exists
        FS::mkdir(Config::get('cache.path') . '/cron_locks/');
        // get lock file lock
        $lockFile = Config::get('cache.path') . '/cron_locks/' . $job->id();
        touch($lockFile);
        static::$locks = fopen($lockFile, 'r');
        if (!flock(static::$locks, LOCK_EX)) {
            fclose(static::$locks);
            return false;
        }
        return true;
    }

    protected static function releaseLock(CronJob $job)
    {
        if ($job->id() === null) return;
        // release and delete file lock
        $lockFile = Config::get('cache.path') . '/cron_locks/' . $job->id();
        flock(static::$locks[$job->id()], LOCK_UN);
        fclose(static::$locks[$job->id()]);
        unlink($lockFile);
    }

    protected static function buildDispatcherJobs()
    {
        foreach (Config::get('cron.intervals') as $name => $interval) {
            $jobs = Dispatcher::getListeners('onCron_' . $name);
            foreach ($jobs as $fn) {
                $runner = new CronJob('DispatcherJobs', static::generateDispatcherJobName($fn), $fn, $name);
                $runner->save(true);
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
            ->where('run_halted is null')
            ->where('run_next <= ' . time())
            ->order('run_next ASC, id ASC')
            ->limit(1);
        if (static::$skip) {
            $query->where('id NOT IN ?', [static::$skip]);
        }
        if ($query->execute() && $result = $query->getResult()) {
            if ($result = $result->fetchObject(CronJob::class)) {
                return $result;
            }
        }
        return null;
    }
}
