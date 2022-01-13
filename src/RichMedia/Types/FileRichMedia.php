<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;

class FileRichMedia extends AbstractRichMedia
{
    public static function class(): string
    {
        return 'file';
    }

    public static function className(): string
    {
        return 'File download (single)';
    }

    public function name(string $set = null): string
    {
        if ($set) {
            $this->name = $set;
        }
        return $this->name ?? $this->file()->filename();
    }

    public function file(): FilestoreFile
    {
        return Filestore::get($this['file']);
    }
}
