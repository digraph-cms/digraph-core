<?php

namespace DigraphCMS\HTML\Forms;

use DateTime;
use DigraphCMS\UI\Theme;

class DateTimeInput extends INPUT
{
    protected $step = 900;

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'datetime-local',
                'step' => $this->step,
                'value' => $this->value(true)
                    ? $this->value(true)->format('Y-m-d\TH:i:s')
                    : null
            ]
        );
    }

    public function setStep($seconds)
    {
        $this->step = $seconds;
        return $this;
    }

    public function value($useDefault = false)
    {
        if ($value = parent::value($useDefault)) {
            if (is_string($value)) {
                return DateTime::createFromFormat('Y-m-d\TH:i:s', $value, Theme::timezone());
            } else {
                return $value;
            }
        }
        return null;
    }
}
