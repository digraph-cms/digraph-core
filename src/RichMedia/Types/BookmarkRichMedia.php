<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\HTML\A;

class BookmarkRichMedia extends AbstractRichMedia
{
    public static function class(): string
    {
        return 'bookmark';
    }

    public static function className(): string
    {
        return 'Bookmark';
    }
}
