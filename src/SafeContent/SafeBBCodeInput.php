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

    public function value(bool $useDefault = false): mixed
    {
        $value = parent::value($useDefault);
        // strip out any disabled/nuisance tags
        $value = SafeBBCode::stripNuisanceTags($value);
        return $value;
    }

    public function __toString()
    {
        SafeBBCode::loadEditorMedia();
        return parent::__toString();
    }
}
