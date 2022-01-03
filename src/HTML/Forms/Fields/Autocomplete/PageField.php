<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

use DigraphCMS\URL\URL;

class PageField extends AutocompleteField
{
    public function __construct(string $label)
    {
        parent::__construct(
            $label,
            new AutocompleteInput(null, new URL('/~api/v1/autocomplete/page.php'))
        );
        $this->addClass('autocomplete-field--page');
    }
}
