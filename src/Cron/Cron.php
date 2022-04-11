<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Events\Dispatcher;

class Cron
{
    public static function run(int $endByTime = null)
    {
        $listeners = Dispatcher::getListeners('onCron');
        shuffle($listeners);
        foreach ($listeners as $listener) {
            try {
                call_user_func($listener);
            } catch (\Throwable $th) {
                // TODO: Log exceptions in cron jobs somewhere, maybe notify admins too
            }
            if ($endByTime && time() >= $endByTime) break;
        }
    }
}
