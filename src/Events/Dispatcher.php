<?php

namespace DigraphCMS\Events;

class Dispatcher
{
    protected static $listeners = [];
    protected static $locations = [];
    protected static $staticIDs = [];
    public static $closeResponseBeforeShutdown = true;

    public static function __shutdown()
    {
        // if close before shutdown is false, don't do anything to close connection
        if (!self::$closeResponseBeforeShutdown) {
            self::dispatchEvent('onShutdown');
            return;
        }
        // try to close connection
        if (is_callable('fastcgi_finish_request')) {
            // preferred method using session_write_close and fastcgi_finish_request
            session_write_close();
            fastcgi_finish_request();
            self::dispatchEvent('onShutdown');
        } else {
            // alternative hacky method, doesn't work as well
            ignore_user_abort(true);
            flush();
            session_write_close();
            ob_start();
            self::dispatchEvent('onShutdown');
            ob_end_clean();
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
     * Returns a random ID that can be used to later remove this event listener.
     *
     * @param string $event
     * @param callable $callback
     * @return string
     */
    public static function addEventListener(string $event, callable $callback): string
    {
        $id = bin2hex(random_bytes(8));
        self::$locations[$id] = $event;
        self::$listeners[$event][$id] = $callback;
        return $id;
    }

    /**
     * Remove an event listener using the id returned by addEventListener()
     *
     * @param string $id
     * @return void
     */
    public static function removeEventListener(string $id)
    {
        if ($event = @self::$locations[$id]) {
            unset(self::$locations[$id]);
            unset(self::$listeners[$event][$id]);
        }
    }

    /**
     * Return the "first" non-null value returned by a listener for the event.
     * 
     * ! Note that order is reversed from usual, so the last added listeners run first
     *
     * @param string $event
     * @param array $args
     * @return void
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
     * Returns an array of all added methods and their IDs that can be used to
     * remove them.
     *
     * @param mixed $object_or_class
     * @return array
     */
    public static function addSubscriber($object_or_class): array
    {
        $ids = [];
        if (is_object($object_or_class)) {
            // add callable [object, method] arrays
            foreach (self::getMethods($object_or_class) as $method) {
                $ids[$method] = self::addEventListener($method, [$object_or_class, $method]);
            }
        } elseif (class_exists($object_or_class)) {
            // add strings of static methods
            foreach (self::getMethods($object_or_class) as $method) {
                $ids[$method] = self::addEventListener($method, "$object_or_class::$method");
            }
            // save IDs for removeSubscriber
            self::$staticIDs[$object_or_class] = $ids;
        }
        return $ids;
    }

    /**
     * Remove a previously-added subscriber
     *
     * @param mixed $object_or_class
     * @return void
     */
    public static function removeSubscriber($object_or_class): void
    {
        if (is_object($object_or_class)) {
            // loop throuugh methods in object
            foreach (self::getMethods($object_or_class) as $method) {
                // look for array callables using this object and remove them
                foreach (self::$listeners[$method] as $id => $callable) {
                    if (is_array($callable) && $callable[0] == $object_or_class) {
                        self::removeEventListener($id);
                    }
                }
            }
        } elseif (class_exists($object_or_class)) {
            // use list of event listener IDs to remove
            foreach (self::$staticIDs[$object_or_class] ?? [] as $id) {
                self::removeEventListener($id);
            }
            // remove the list
            unset(self::$staticIDs[$object_or_class]);
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
                return preg_match('/^on([A-Z]+[a-z_]*[a-zA-Z0-9]*)+$/', $e);
            }
        );
    }
}

register_shutdown_function(Dispatcher::class . '::__shutdown');