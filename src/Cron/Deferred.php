<?php

namespace DigraphCMS\Cron;

use DigraphCMS\DB\DB;

class Deferred
{
    protected static $skip = [];

    public static function runJobs(string $group = null, int $endByTime = null): int
    {
        // count number of jobs run
        $count = 0;
        // proceed with jobs one at a time
        while ((!$endByTime || time() < $endByTime) && $job = static::getNextJob($group)) {
            // don't make more than one attempt per job
            static::$skip[] = $job->id();
            // execute job
            $count += $job->execute() ? 1 : 0;
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
            ->order('id ASC')
            ->limit(1);
        if ($group) {
            $query->where('`group` = ?', [$group]);
        }
        if (static::$skip) {
            $query->where('id NOT IN (?)', [static::$skip]);
        }
        if (@$query->execute() && $result = $query->getResult()) {
            if ($result = $result->fetchObject(DeferredJob::class)) {
                return $result;
            }
        }
        return null;
    }
}
