<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Datastore\Datastore;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\UI\Format;

class ScheduledJob
{
    /**
     * @param callable $job
     * @param Schedule|Schedule[] $schedules
     * @param string $job_name
     * @return void
     */
    public function schedule(callable $job, Schedule|array $schedules, string $job_name): void
    {
        if (!is_array($schedules)) $schedules = [$schedules];
        /** internal name to use for this job group */
        $job_group = 'sched_' . md5($job_name);
        /** hash of settings so we can clear and rebuild jobs only if it is different from what was last entered */
        $hash = md5(Digraph::serialize([
            $job,
            $schedules,
        ]));
        // check if last build of this job was the same
        if (Datastore::value('system', 'scheduled_jobs', $job_name) == $hash) return;
        else {
            // this call is different from the last build of this job, we need to rebuild it
            // first clear all jobs with this group
            DB::query()->delete('defex')
                ->where('group', $job_group)
                ->execute();
            // next create new job that will handle future runs of this job
            new DeferredJob(
                $this->buildInitialization($job, $schedules, $job_group),
                $job_group,
                null,
            );
            // save hash in datastore
            Datastore::set(
                'system',
                'scheduled_jobs',
                $job_name,
                $hash,
                ['job_group' => $job_group]
            );
        }
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
        return static::scheduleNext($job, $schedules, $group);
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
        return sprintf(
            '%s (%s)',
            call_user_func($job, $deferred_job),
            static::scheduleNext($job, $schedules, $group)
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