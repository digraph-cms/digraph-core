<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

class PageField extends AutocompleteField
{
    public function __construct(string $label)
    {
        parent::__construct(
            $label,
            new PageInput()
        );
        $this->addClass('autocomplete-field--page');
    }
}
