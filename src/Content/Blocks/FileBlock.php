<?php

namespace DigraphCMS\Content\Blocks;

class FileBlock extends AbstractBlock
{
    public static function class(): string
    {
        return 'file';
    }

    public static function className(): string
    {
        return 'File download';
    }

    public function icon(): string
    {
        return '&#xeb12;';
    }
}
