<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Content\Graph;
use DigraphCMS\Content\Pages;

class RecursivePageJob extends DeferredJob
{
    public function __construct(string $uuid, callable $function = null, $leavesFirst = false, string $group = null, array $parents = [])
    {
        $function = function (DeferredJob $job) use ($uuid, $function, $leavesFirst, $parents) {
            return static::prepareRecursiveJob($job, $uuid, $function, $leavesFirst, $parents);
        };
        parent::__construct($function, $group);
    }

    public static function prepareRecursiveJob(DeferredJob $job, string $uuid, callable $function, bool $leavesFirst, array $parents)
    {
        // check if we're in a cycle
        if (in_array($uuid, $parents)) return "Cycle detected: " . implode(' => ', $parents) . " => $uuid";
        // otherwise continue as normal
        $parents[] = $uuid;
        $page = Pages::get($uuid);
        if (!$page) return "Page $page not found";
        if ($leavesFirst) static::spawnChildJobs($job, $uuid, $function, $leavesFirst, $parents);
        static::spawnParentJob($job, $uuid, $function);
        if (!$leavesFirst) static::spawnChildJobs($job, $uuid, $function, $leavesFirst, $parents);
        return "Prepared recursive jobs for $uuid";
    }

    public static function spawnParentJob(DeferredJob $job, string $uuid, callable $function)
    {
        $job->spawn(function (DeferredJob $job) use ($uuid, $function) {
            $page = Pages::get($uuid);
            if (!$page) return "Page $page not found";
            return call_user_func($function, $job, $page) ?? 'No output produced';
        });
    }

    public static function spawnChildJobs(DeferredJob $job, string $uuid, callable $function, bool $leavesFirst, array $parents)
    {
        $children = Graph::childIDs($uuid);
        while ($row = $children->fetch()) {
            $child = $row['end_page'];
            new RecursivePageJob($child, $function, $leavesFirst, $job->group(), $parents);
        }
    }
}
