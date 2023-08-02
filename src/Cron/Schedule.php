<?php

namespace DigraphCMS\Cron;

abstract class Schedule
{
    /**
     * Set this to a timestamp after which this job expires, if necessary 
     * @var int|null 
     */
    protected $expires = null;

    /**
     * Build a list of potential next run times. Doesn't need to be super
     * thorough, as they wil be automatically filtered to only include actual
     * valid future values, and the earliest one will be used. Also
     * automatically filters out values that are after the expiration date if
     * one is set.
     * 
     * Can return null or an empty array to indicate that this schedule is no
     * longer valid or expired, and the job should not be re-scheduled.
     *
     * @return integer[]|null
     */
    abstract protected function potentialTimes(): array|null;

    /** @var int|null|false */
    protected $nextRun = false;

    /**
     * Set the timstamp after which this job expires. Null to never expire.
     *
     * @param integer|null $expires
     * @return static
     */
    public function setExpires(int|null $expires): static
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * Return the next time this job should be rescheduled for, if applicable.
     * Returns null if the job has no more scheduled run and should not be
     * re-scheduled. This allows schedules to expire after a certain amount of
     * time, and not always get re-scheduled forever.
     *
     * @return integer|null
     */
    public function nextRun(): ?int
    {
        if ($this->nextRun === false) {
            // first filter out all times that are not in the future (with a 10 minute buffer)
            $next = array_filter(
                $this->potentialTimes(),
                fn($time) => $time > (time() + 600),
            );
            // then filter out all times that are after the expires time
            if (!is_null($this->expires)) {
                $next = array_filter(
                    $next,
                    fn($time) => $time <= $this->expires
                );
            }
            // cache results
            if (!$next) $next = null;
            else $this->nextRun = min($next);
        }
        return $this->nextRun();
    }
}