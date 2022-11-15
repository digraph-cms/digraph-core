<?php

namespace DigraphCMS\SafeContent;

use DigraphCMS\HTML\Forms\TEXTAREA;

class SafeBBCodeInput extends TEXTAREA
{
    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'safe-bbcode-input',
                'safe-bbcode-input--nojs'
            ]
        );
    }

    public function __toString()
    {
        SafeBBCode::loadEditorMedia();
        return parent::__toString();
    }
}
