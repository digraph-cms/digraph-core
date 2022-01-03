<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

use DigraphCMS\Content\Pages;
use DigraphCMS\HTML\Forms\InputInterface;
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
        $this->addValidator(function (InputInterface $input): ?string {
            if (!Pages::exists($input->value())) {
                return sprintf("%s is not a valid page", $input->value());
            }
            return null;
        });
    }
}
