<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

use DigraphCMS\HTML\Forms\InputInterface;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

class UserField extends AutocompleteField
{
    public function __construct(string $label)
    {
        parent::__construct(
            $label,
            new AutocompleteInput(null, new URL('/~api/v1/autocomplete/user.php'))
        );
        $this->addClass('autocomplete-field--user');
        $this->addValidator(function (InputInterface $input): ?string {
            if ($input->value() && !Users::exists($input->value())) {
                return sprintf("%s is not a valid user", $input->value());
            }
            return null;
        });
    }
}
