<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

class UserField extends AutocompleteField
{
    public function __construct(string $label)
    {
        parent::__construct(
            $label,
            new UserInput()
        );
        $this->addClass('autocomplete-field--user');
    }
}
