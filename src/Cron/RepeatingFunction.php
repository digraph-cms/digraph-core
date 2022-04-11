<?php

namespace DigraphCMS\Cron;

use DigraphCMS\UI\Format;

class RepeatingFunction extends DelayedFunction
{
    protected $interval;
    protected $endAfterTime;
    protected $endAfterCount;
    protected $count = 0;
    protected $abort = false;

    public function __construct(callable $fn, int $interval)
    {
        parent::__construct($fn);
        $this->interval = $interval;
    }

    public function setEndAfterTime($date)
    {
        $this->endAfterTime = Format::parseDate($date);
        return $this;
    }

    public function setEndAfterCount(bool $count)
    {
        $this->endAfterCount = $count;
        return $this;
    }

    public function setAbort(bool $abort)
    {
        $this->abort = $abort;
    }

    public function run()
    {
        $error = null;
        $this->count++;
        try {
            // try to execute
            parent::run();
        } catch (\Throwable $error) {
            //throw $error;
        }
        // determine if we need to reschedule
        $reschedule = true;
        if ($this->abort) $reschedule = false;
        elseif ($error) $reschedule = false;
        elseif ($this->endAfterCount && $this->count >= $this->endAfterCount) $reschedule = false;
        elseif ($this->endAfterTime && time() >= $this->endAfterTime->getTimestamp()) $reschedule = false;
        // reschedule if necessary
        if ($reschedule) {
            $copy = clone $this;
            $copy->setScheduled(time() + $this->interval);
            $copy->save();
        }
        // re-throw error if necessary, so things log properly
        if ($error) throw $error;
    }
}
