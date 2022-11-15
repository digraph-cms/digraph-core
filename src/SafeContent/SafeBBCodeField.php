<?php

namespace DigraphCMS\SafeContent;

use DigraphCMS\HTML\Forms\Field;

class SafeBBCodeField extends Field
{
    public function __construct(string $label, SafeBBCodeInput $input = null)
    {
        parent::__construct($label, $input ?? new SafeBBCodeInput);
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'safe-bbcode-field',
                'safe-bbcode-field--nojs'
            ]
        );
    }
}
