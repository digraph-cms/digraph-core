<?php

namespace DigraphCMS\Content\Blocks;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;

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

    public function html_editor(): string
    {
        return sprintf(
            '<div class="file-block file-block--extension-%s"><span class="file-block__icon">%s</span>' . PHP_EOL . '<span class="file-block__label">%s</span></div>',
            $this->file()->extension(),
            $this->icon(),
            $this->name()
        );
    }

    public function html_public(): string
    {
        return sprintf(
            '<div class="file-block file-block--extension-%s"><a href="%s"><span class="file-block__icon">%s</span>' . PHP_EOL . '<span class="file-block__label">%s</span></a></div>',
            $this->file()->extension(),
            $this->file()->url(),
            $this->icon(),
            $this->name()
        );
    }

    public function file(): FilestoreFile
    {
        return Filestore::get($this['file']);
    }
}
