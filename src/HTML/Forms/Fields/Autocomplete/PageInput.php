<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

use DigraphCMS\Content\Pages;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTML\Forms\InputInterface;
use DigraphCMS\URL\URL;

class PageInput extends AutocompleteInput
{
    public function __construct(string $id = null, URL $endpoint = null)
    {
        // pass endpoint and set up card-generating function
        parent::__construct(
            $id,
            $endpoint ?? new URL('/api/v1/autocomplete/page.php'),
            function (string $value) {
                if ($page = Pages::get($value)) {
                    return Dispatcher::firstValue('onPageAutocompleteCard', [$page]);
                }
                return null;
            }
        );
        // add validator to ensure the value exists
        $this->addValidator(function (InputInterface $input): ?string {
            if ($input->value() && !Pages::exists($input->value())) {
                return sprintf("%s is not a valid page", $input->value());
            }
            return null;
        });
        // add CSS class
        $this->addClass('autocomplete-input--page');
    }
}
