<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTML\Forms\InputInterface;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

class UserInput extends AutocompleteInput
{
    public function __construct(string $id = null, URL $endpoint = null)
    {
        // pass endpoint and set up card-generating function
        parent::__construct(
            $id,
            $endpoint ?? new URL('/~api/v1/autocomplete/user.php'),
            function (string $value) {
                if ($user = Users::get($value)) {
                    return Dispatcher::firstValue('onUserAutoCompleteCard', [$user]);
                }
                return null;
            }
        );
        // add validator to ensure the value exists
        $this->addValidator(function (InputInterface $input): ?string {
            if ($input->value() && !Users::exists($input->value())) {
                return sprintf("%s is not a valid user", $input->value());
            }
            return null;
        });
        // add CSS class
        $this->addClass('autocomplete-input--user');
    }
}
