<?php

namespace DigraphCMS\HTML\Forms\Fields;

use DigraphCMS\HTML\Forms\DateInput;
use DigraphCMS\HTML\Forms\Field;

class DateField extends Field
{
    public function __construct(string $label)
    {
        parent::__construct($label, new DateInput());
    }
}
