<?php

namespace DigraphCMS\Cron;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\UI\Format;

class DelayedFunction
{
    protected $fn;
    protected $scheduled, $expires;
    protected $parent, $label;

    public function __construct(callable $fn)
    {
        $this->fn = $fn;
        $this->scheduled = new DateTime();
        $this->expires = null;
    }

    public function run()
    {
        call_user_func($this->fn, $this);
    }

    public function save()
    {
        DB::query()->insertInto(
            'delex',
            [
                'parent' => $this->parent(),
                'label' => $this->label(),
                'created' => time(),
                'scheduled' => $this->scheduled()->getTimestamp(),
                'expires' => $this->expires() ? $this->expires()->getTimestamp() : null,
                'runner' => \Opis\Closure\serialize($this)
            ]
        )->execute();
    }

    public function setParent(?string $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    public function parent(): ?string
    {
        return $this->parent;
    }

    public function setLabel(?string $label)
    {
        $this->label = $label;
        return $this;
    }

    public function label(): ?string
    {
        return $this->label;
    }

    public function setScheduled($time)
    {
        $this->scheduled = Format::parseDate($time);
        return $this;
    }

    public function scheduled(): DateTime
    {
        return $this->scheduled;
    }

    public function setExpires($time)
    {
        $this->expires = $time !== null ? Format::parseDate($time) : null;
        return $this;
    }

    public function expires(): ?DateTime
    {
        return $this->expires;
    }
}
