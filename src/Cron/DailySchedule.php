<?php

namespace DigraphCMS\Cron;

class DailySchedule extends Schedule
{
    /** @var int[] */
    protected $hours = [];

    /**
     * @param integer|integer[] $hours 0-23
     */
    public function __construct(
        int|array $hours,
    ) {
        if (!is_array($hours)) $hours = [$hours];
        $this->hours = $hours;
    }

    function potentialTimes(): array
    {
        $next = [];
        foreach ($this->hours as $hour) {
            $next[] = strtotime(sprintf('today %s:00', $hour));
            $next[] = strtotime(sprintf('tomorrow %s:00', $hour));
        }
        return $next;
    }
}