<?php

namespace DigraphCMS\HTML\Forms;

use DateTime;
use DigraphCMS\UI\Theme;

class DateInput extends INPUT
{
    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'date',
                'value' => $this->value(true) ? $this->value(true)->format('Y-m-d') : null
            ]
        );
    }

    public function value($useDefault = false)
    {
        if ($value = parent::value($useDefault)) {
            if (is_string($value)) {
                $value = DateTime::createFromFormat('Y-m-d', $value, Theme::timezone()->getName());
                $value->setTime(0, 0, 0, 0);
                return $value;
            } else {
                return $value;
            }
        }
        return null;
    }
}
