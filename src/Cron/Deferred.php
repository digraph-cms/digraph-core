<?php

namespace DigraphCMS\Cron;

use DigraphCMS\DB\DB;
use Exception;

class Deferred
{
    protected static $skip = [];

    public static function runJobs(string $group = null, int $endByTime = null): int
    {
        // count number of jobs run
        $count = 0;
        // proceed with jobs one at a time
        while ((!$endByTime || time() < $endByTime) && ($job = static::getNextJob($group))) {
            // don't make more than one attempt per job
            if (in_array($job->id(), static::$skip)) throw new Exception('Tried to run defex job ' . $job->id() . ' again after skipping it');
            static::$skip[] = $job->id();
            // execute job
            if ($job->execute()) $count++;
        }
        // return number of jobs run
        return $count;
    }

    public static function groupCount(string $group, bool $complete = null): int
    {
        $query = DB::query()->from('defex')
            ->where('`group` = ?', [$group]);
        if ($complete !== null) {
            if ($complete) $query->where('run is not null');
            else $query->where('run is null');
        }
        return $query->count();
    }

    public static function getNextJob(string $group = null): ?DeferredJob
    {
        $query = DB::query()->from('defex')
            ->where('run is null')
            ->where('(scheduled is null OR scheduled <= ?)', [time()])
            ->order('scheduled ASC, id ASC')
            ->limit(1);
        if ($group) {
            $query->where('`group` = ?', [$group]);
        }
        foreach (static::$skip as $skip) {
            $query->where('id <> ?', $skip);
        }
        $query->execute();
        if ($result = $query->getResult()) {
            if ($result = $result->fetchObject(DeferredJob::class)) {
                return $result;
            }
        }
        return null;
    }
}
