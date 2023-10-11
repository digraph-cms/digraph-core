<?php

namespace DigraphCMS\Cron;

use DigraphCMS\DB\DB;
use DigraphCMS\Email\EmailCronSubscriber;
use DigraphCMS\Plugins\Plugins;
use Exception;

class Cron
{
    protected static $skip = [];

    public static function runJobs(int $deadlineTime = null): int
    {
        // count number of jobs run
        $count = 0;
        // prepare jobs from core tools and plugins
        static::registerSubscriber(CoreCronSubscriber::class);
        static::registerSubscriber(EmailCronSubscriber::class);
        foreach (Plugins::plugins() as $plugin) static::registerSubscriber($plugin);
        // proceed with jobs one at a time
        while ((!$deadlineTime || time() < $deadlineTime) && ($job = static::getNextJob())) {
            // don't make more than one attempt per job
            if (in_array($job->id(), static::$skip)) throw new Exception('Tried to run cron job ' . $job->id() . ' again after skipping it');
            static::$skip[] = $job->id();
            // execute job
            if ($job->execute($deadlineTime)) $count++;
        }
        // run Deferred jobs afterwards
        $count += Deferred::runJobs(null, $deadlineTime);
        // return number of jobs run
        return $count;
    }

    public static function registerSubscriber($object_or_class)
    {
        if (is_object($object_or_class)) $class = get_class($object_or_class);
        elseif (class_exists($object_or_class)) $class = $object_or_class;
        else throw new \Exception("Cron subscriber must be an object or class");
        // add strings of static methods
        foreach (self::getMethods($class) as $method) {
            new CronJob(
                'CronSubscriber',
                "$class::$method",
                function (CronJob $job, int $deadline = null) use ($class, $method) {
                    static::runSubscriberJob($job, $deadline, $class, $method);
                },
                substr($method, 8)
            );
        }
    }

    protected static function runSubscriberJob(CronJob $job, ?int $deadline, $class, $method)
    {
        if (!method_exists($class, $method)) $job->delete();
        call_user_func([$class, $method], $job, $deadline);
    }

    /**
     * Return the methods of a given object or class that look like they could
     * be event names.
     *
     * @param mixed $object_or_class
     * @return array
     */
    protected static function getMethods($object_or_class): array
    {
        return array_filter(
            get_class_methods($object_or_class),
            function ($e) {
                return
                    substr($e, 0, 8) == 'cronJob_'
                    && preg_match('/^cronJob_[a-z]+(_[a-z]+)*$/', $e);
            }
        );
    }

    protected static function getNextJob(): ?CronJob
    {
        $query = DB::query()->from('cron')
            ->where('run_next <= ?', [time()])
            ->order('run_next ASC, id ASC')
            ->limit(1);
        foreach (static::$skip as $skip) {
            $query->where('id <> ?', $skip);
        }
        $query->execute();
        if ($result = $query->getResult()) {
            if ($result = $result->fetchObject(CronJob::class)) {
                return $result;
            }
        }
        return null;
    }
}
