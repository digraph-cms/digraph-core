<?php

namespace DigraphCMS\RichMedia\Types;

class TableRichMedia extends AbstractRichMedia
{
    public static function class(): string
    {
        return 'table';
    }

    public static function className(): string
    {
        return 'Table';
    }
}
