<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Datastore\Datastore;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\UI\Format;

class ScheduledJobs
{
    /**
     * @param callable $job
     * @param Schedule|Schedule[] $schedules
     * @param string $job_name
     * @param mixed $hash pass your own non-null values to override hashing and avoid unnecessary rebuilds/reinitialization
     * @return string the internal job group name
     */
    public static function schedule(callable $job, Schedule|array $schedules, string $job_name, mixed $hash = null): string
    {
        if (!is_array($schedules)) $schedules = [$schedules];
        /** internal group name to use for this job group */
        $group_name = static::groupName($job_name);
        /** hash of settings so we can clear and rebuild jobs only if it is different from what was last entered */
        // NOTE: can be overridden if a job includes things that don't hash consistently (which is likely)
        if (is_null($hash)) $hash = md5(Digraph::serialize([
                $job,
                $schedules,
            ]));
        else $hash = md5(Digraph::serialize($hash));
        // check if last build of this job was the same
        if (static::storedHash($job_name) == $hash) {
            // stored hash matches new hash, so there's nothing to update
            return $group_name;
        } else {
            // this call is different from the last build of this job, we need to rebuild it
            // first clear all non-executed jobs with this group
            DB::query()->delete('defex')
                ->where('`group`', $group_name)
                ->where('`run` is null')
                ->execute();
            // next create new job that will handle future runs of this job
            new DeferredJob(
                static::buildInitialization($job, $schedules, $group_name),
                $group_name,
                null,
            );
            // save hash in datastore
            static::setStoredHash($job_name, $hash);
        }
        // return internal job group name
        return $group_name;
    }

    protected static function setStoredHash(string $job_name, string $hash): void
    {
        Datastore::set(
            'system',
            'scheduled_jobs',
            $job_name,
            $hash
        );
    }

    public static function storedHash(string $job_name): ?string
    {
        return Datastore::value('system', 'scheduled_jobs', $job_name);
    }

    public static function groupName($job_name): string
    {
        return 'sched_' . md5($job_name);
    }

    /**
     * Return a callback that will run the initialization process for this job,
     * to set it to run on all specified schedules.
     *
     * @param callable $job
     * @param Schedule[] $schedules
     * @param string $group
     * @return callable
     */
    protected static function buildInitialization(callable $job, array $schedules, string $group): callable
    {
        return function () use ($job, $schedules, $group) {
            return static::initialize($job, $schedules, $group);
        };
    }

    /**
     * Do initialization to set scheduled jobs for the specified job and all given schedules.
     * 
     * @param callable $job
     * @param Schedule[] $schedules
     * @param string $group
     * @return string
     */
    protected static function initialize(callable $job, array $schedules, string $group): string
    {
        return 'Initialized first run: ' . static::scheduleNext($job, $schedules, $group);
    }

    /**
     * Schedule the next run for the given job/schedules/group and return a
     * human-readable string indicating when the next run is (or if there is none)
     * 
     * @param callable $job
     * @param Schedule[] $schedules
     * @param string $group
     * @return string
     */
    protected static function scheduleNext(callable $job, array $schedules, string $group): string
    {
        $next = static::nextRun($schedules);
        // there is no next run
        if (is_null($next)) return "No next run";
        // schedule for next time
        new DeferredJob(
            static::buildCallback($job, $schedules, $group),
            $group,
            $next
        );
        return sprintf('Next run %s', Format::datetime($next, true));
    }

    /**
     * @param callable $job
     * @param Schedule[] $schedules
     * @param string $group
     * @return callable
     */
    protected static function buildCallback(callable $job, array $schedules, string $group): callable
    {
        return function (DeferredJob $deferred_job) use ($job, $schedules, $group): string {
            return static::callback($deferred_job, $job, $schedules, $group);
        };
    }

    /**
     * @param DeferredJob $deferred_job
     * @param callable $job
     * @param Schedule[] $schedules
     * @param string $group
     * @return string
     */
    protected static function callback(DeferredJob $deferred_job, callable $job, array $schedules, string $group): string
    {
        // schedule next run
        $next = static::scheduleNext($job, $schedules, $group);
        // spawns job in its own DeferredJob so that it can't break rescheduling if it throws an exception
        $deferred_job->spawn($job);
        // return status
        return sprintf(
            'Spawned runner (%s)',
            $next
        );
    }

    /**
     * Get the nearest nextRun() value from an array of Schedules. Returns
     * null if no non-null values are returned (which indicates that this job
     * should not be rescheduled)
     * 
     * @param Schedule[] $schedules
     * @return int|null
     */
    protected static function nextRun(array $schedules): int|null
    {
        $next = [];
        foreach ($schedules as $schedule) {
            $schedule_next = $schedule->nextRun();
            if (!is_null($schedule_next)) $next[] = $schedule_next;
        }
        if (!$next) return null;
        else return min($next);
    }
}