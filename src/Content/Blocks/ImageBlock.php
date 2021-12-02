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

    public function html_editor(): string
    {
        return '<div>Block ' . $this->uuid().'</div>';
    }

    public function html_public(): string
    {
        return '<div>Public view of ' . $this->uuid().'</div>';
    }
}
