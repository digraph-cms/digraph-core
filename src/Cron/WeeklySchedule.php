<?php

namespace DigraphCMS\Cron;

class WeeklySchedule extends Schedule
{
    const MONDAY = 'Monday';
    const TUESDAY = 'Tuesday';
    const WEDNESDAY = 'Wednesday';
    const THURSDAY = 'Thursday';
    const FRIDAY = 'Friday';
    const SATURDAY = 'Saturday';
    const SUNDAY = 'Sunday';

    const WEEKDAYS = [self::MONDAY, self::TUESDAY, self::WEDNESDAY, self::THURSDAY, self::FRIDAY];
    const WEEKENDS = [self::SATURDAY, self::SUNDAY];
    const WEEKNIGHTS = [self::SUNDAY, self::MONDAY, self::TUESDAY, self::WEDNESDAY, self::THURSDAY];
    const MWF = [self::MONDAY, self::WEDNESDAY, self::FRIDAY];
    const TR = [self::TUESDAY, self::THURSDAY];

    /** @var string[] */
    protected $days = [];
    /** @var int[] */
    protected $hours = [];

    /**
     * @param integer|integer[] $hours 0-23
     * @param string|string[]   $days  constants or valid string representations for use in strtotime()
     */
    public function __construct(
        int|array    $hours,
        string|array $days,
    )
    {
        // set up hours
        if (!is_array($hours)) $hours = [$hours];
        $this->hours = $hours;
        // set up days
        if (!is_array($days)) $this->days = [$days];
        else $this->days = $days;
    }

    function potentialTimes(): array
    {
        $next = [];
        foreach ($this->days as $day) {
            foreach ($this->hours as $hour) {
                $next[] = strtotime(sprintf('%s %s:00', $day, $hour));
                $next[] = strtotime(sprintf('next %s %s:00', $day, $hour));
            }
        }
        return $next;
    }
}