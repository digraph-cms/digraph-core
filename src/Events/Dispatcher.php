<?php

namespace DigraphCMS\Events;

use DigraphCMS\Cache\Locking;
use DigraphCMS\CoreEventSubscriber;
use DigraphCMS\Cron\CronJob;

// register core event subscriber
Dispatcher::addSubscriber(CoreEventSubscriber::class);
register_shutdown_function([Dispatcher::class, 'shutdownFunction']);

class Dispatcher
{
    protected static $listeners = [];
    protected static $locations = [];
    protected static $staticIDs = [];
    protected static $cronJobs = [];

    public static function shutdownFunction()
    {
        if (Locking::lock('DispatcherCronJobs', true, 60)) {
            foreach (static::$cronJobs as $job) call_user_func($job);
        }
    }

    /**
     * Remove all listeners/subscribers
     *
     * @return void
     */
    public static function reset()
    {
        self::$listeners = [];
        self::$locations = [];
        self::$staticIDs = [];
    }

    /**
     * Add a callback to be executed when the specified event is dispatched.
     *
     * @param string $event
     * @param callable $callback
     * @return void
     */
    public static function addEventListener(string $event, callable $callback)
    {
        // check if this is a cron event
        if (substr($event, 0, 7) === 'onCron_') {
            static::$cronJobs[] = function () use ($event, $callback) {
                $name = static::generateCronJobName($callback);
                new CronJob('Dispatcher', $name, $callback, substr($event, 7));
                return;
            };
        }
        // add normally otherwise
        self::$locations[] = $event;
        self::$listeners[$event][] = $callback;
    }

    protected static function generateCronJobName($fn)
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


    /**
     * Retrieve the raw callables of all event listeners for a given event
     *
     * @param string $event
     * @return callable[]
     */
    public static function getListeners(string $event): array
    {
        $event = static::normalizeEventName($event);
        return self::$listeners[$event] ?? [];
    }

    /**
     * Return the "first" non-null value returned by a listener for the event.
     * 
     * ! Note that order is reversed from usual, so the last added listeners run first
     *
     * @param string $event
     * @param array $args
     * @return mixed
     */
    public static function firstValue(string $event, array $args = [])
    {
        $event = static::normalizeEventName($event);
        foreach (array_reverse(self::$listeners[$event] ?? []) as $callback) {
            if (null !== ($value = call_user_func_array($callback, $args))) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Execute all callbacks associated with a given event name
     *
     * @param string $event
     * @param array $args
     * @return void
     */
    public static function dispatchEvent(string $event, array $args = [])
    {
        $event = static::normalizeEventName($event);
        foreach (self::$listeners[$event] ?? [] as $callback) {
            if (call_user_func_array($callback, $args) === false) {
                break;
            }
        }
    }

    /**
     * Ensure that event names only contain characters that are valid function
     * names so that they can be called on listeners/subscribers.
     *
     * @param string $name
     * @return string
     */
    protected static function normalizeEventName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    }

    /**
     * Add a subscriber, from either an instantiated object or static class. In
     * either case this means going through all public methods and adding
     * listeners for any that look like event names 
     * (i.e. onCamelCaseName_optionalMore)
     *
     * @param mixed $object_or_class
     * @return void
     */
    public static function addSubscriber($object_or_class)
    {
        if (is_object($object_or_class)) {
            // add callable [object, method] arrays
            foreach (self::getMethods($object_or_class) as $method) {
                self::addEventListener($method, [$object_or_class, $method]);
            }
        } elseif (class_exists($object_or_class)) {
            // add strings of static methods
            foreach (self::getMethods($object_or_class) as $method) {
                self::addEventListener($method, "$object_or_class::$method");
            }
        }
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
                    substr($e, 0, 2) == 'on'
                    && preg_match('/^on([A-Z]+[a-z_]*[a-zA-Z0-9]*)+$/', $e);
            }
        );
    }
}
