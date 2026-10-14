<?php

namespace DigraphCMS\HTML\Forms;

use DateTimeImmutable;
use DateTimeInterface;
use DigraphCMS\UI\Theme;

class DateTimeInput extends INPUT
{

    protected $step = 900;

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type'  => 'datetime-local',
                'step'  => $this->step,
                'value' => $this->value(true)
                    ? $this->value(true)->format('Y-m-d\TH:i')
                    : null
            ],
        );
    }

    public function setStep($seconds)
    {
        $this->step = $seconds;
        return $this;
    }

    public function value($useDefault = false): DateTimeInterface|null
    {
        if ($value = parent::value($useDefault)) {
            if (is_string($value)) {
                return DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, Theme::timezone());
            }
            else {
                return $value;
            }
        }
        return null;
    }

}