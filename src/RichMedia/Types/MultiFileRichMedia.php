<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;

class MultiFileRichMedia extends AbstractRichMedia
{
    public static function class(): string
    {
        return 'multifile';
    }

    public static function className(): string
    {
        return 'File download bundle';
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
