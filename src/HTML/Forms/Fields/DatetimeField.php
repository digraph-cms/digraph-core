<?php

namespace DigraphCMS\HTML\Forms\Fields;

use DigraphCMS\HTML\Forms\DateTimeInput;
use DigraphCMS\HTML\Forms\Field;

class DatetimeField extends Field
{
    public function __construct(string $label)
    {
        parent::__construct($label, new DateTimeInput());
    }

    public function input(): DateTimeInput
    {
        return parent::input();
    }

    public function setStep($seconds)
    {
        $this->input()->setStep($seconds);
        return $this;
    }
}
