<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\HTML\Tag;

class ToolbarSpacer extends Tag
{
    protected $tag = 'span';

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'rich-content-editor__toolbar__spacer'
            ]
        );
    }
}