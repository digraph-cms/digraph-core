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
            '<div class="attachment-block attachment-block-%s block-file">%s %s</div>',
            $this->file()->extension(),
            $this->icon(),
            $this->file()->filename()
        );
    }

    public function html_public(): string
    {
        return sprintf(
            '<div class="attachment-block attachment-block-%s block-file"><a href="%s">%s %s</a></div>',
            $this->file()->extension(),
            $this->file()->url(),
            $this->icon(),
            $this->file()->filename()
        );
    }

    public function file(): FilestoreFile
    {
        return Filestore::get($this['file']);
    }
}
