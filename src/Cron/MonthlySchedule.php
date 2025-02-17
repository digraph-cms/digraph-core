<?php

namespace DigraphCMS\Cron;

use RuntimeException;

class MonthlySchedule extends Schedule {
    /** @var int[] */
    protected $days = [];
    /** @var int */
    protected $hour = 12;

    /**
     * @param integer|integer[] $days 1-28|-1-(-28)
     * @param integer $hour 0-23
     */
    public function __construct(
        int|array $days,
        int $hour = 12
    ) {
        // set up days
        if (!is_array($days)) $this->days = [$days];
        else $this->days = $days;
        // validate days
        foreach ($this->days as $day) {
            if ($day == 0) throw new RuntimeException('Day cannot be 0');
            if ($day > 28) throw new RuntimeException('Day cannot be greater than 28');
            if ($day < -28) throw new RuntimeException('Day cannot be less than -28');
        }
        // set up hour
        $this->hour = $hour;
    }

    function potentialTimes(): array
    {
        $next = [];
        foreach ($this->days as $day) {
            if ($day == 0) throw new RuntimeException('Day cannot be 0');
            if ($day == 1) {
                $next[] = strtotime(sprintf('first day of this month %s:00', $this->hour));
                $next[] = strtotime(sprintf('first day of next month %s:00', $this->hour));
            }
            if ($day > 1) {
                $base = strtotime(sprintf('first day of this month %s:00', $this->hour));
                $next[] = strtotime(sprintf('%s days', $day), $base);
                $base = strtotime(sprintf('first day of next month %s:00', $this->hour));
                $next[] = strtotime(sprintf('%s days', $day), $base);
            }
            if ($day == -1) {
                $next[] = strtotime(sprintf('last day of this month %s:00', $this->hour));
                $next[] = strtotime(sprintf('last day of next month %s:00', $this->hour));
            }
            if ($day < -1) {
                $base = strtotime(sprintf('last day of this month %s:00', $this->hour));
                $next[] = strtotime(sprintf('-%s days', $day), $base);
                $base = strtotime(sprintf('last day of next month %s:00', $this->hour));
                $next[] = strtotime(sprintf('-%s days', $day), $base);
            }
        }
        return $next;
    }
}