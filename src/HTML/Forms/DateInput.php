<?php

namespace DigraphCMS\HTML\Forms;

use DateTime;

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
                return DateTime::createFromFormat('Y-m-d', $value);
            }else {
                return $value;
            }
        }
        return null;
    }
}
