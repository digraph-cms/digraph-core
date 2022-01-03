<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

use DigraphCMS\HTML\Forms\Field;

class AutocompleteField extends Field
{
    public function __construct(string $label, AutocompleteInput $input)
    {
        $this->input = $input;
        parent::__construct($label, $input);
        $this->addClass('autocomplete-field');
    }
}
