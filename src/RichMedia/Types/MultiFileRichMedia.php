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

    /**
     * Undocumented function
     *
     * @return FilestoreFile[]
     */
    public function files(): array
    {
        return array_map(
            Filestore::class . '::get',
            $this['files']
        );
    }
}
