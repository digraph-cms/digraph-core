<?php

namespace DigraphCMS\Content\Blocks;

class ImageBlock extends AbstractBlock
{
    public static function class(): string
    {
        return 'image';
    }

    public static function className(): string
    {
        return 'Image embed';
    }

    public function icon(): string
    {
        return '&#xef4b;';
    }
}
