<?php

namespace DigraphCMS\UI\Toolbars;

use DigraphCMS\HTML\Tag;

class ToolbarSpacer extends Tag
{
    protected $tag = 'span';

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'toolbar__spacer'
            ]
        );
    }
}