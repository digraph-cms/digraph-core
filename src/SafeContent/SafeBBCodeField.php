<?php

namespace DigraphCMS\SafeContent;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\SMALL;
use DigraphCMS\HTML\Text;

class SafeBBCodeField extends Field
{
    public function __construct(string $label, SafeBBCodeInput $input = null)
    {
        parent::__construct($label, $input ?? new SafeBBCodeInput);
        $this->tips()->addChild(
            (new SMALL())
                ->addChild(new Text('Formatted with BBCode. A WYSIWYG editor would appear here if Javascript were enabled.'))
                ->addClass('form-field__tips__tip')
                ->addClass('safe-bbcode-field__nojs-tip')
        );
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
