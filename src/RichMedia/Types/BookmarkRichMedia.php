<?php

namespace DigraphCMS\RichMedia\Types;

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

    public static function description(): string
    {
        return 'Reusable link to either an internal page or an external URL';
    }
}
