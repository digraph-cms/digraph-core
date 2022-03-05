<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ImageRichMedia extends AbstractRichMedia
{
    /**
     * Generate a shortcode rendering of this media
     *
     * @param ShortcodeInterface $code
     * @param self $media
     * @return string|null
     */
    public static function shortCode(ShortcodeInterface $code, $media): ?string
    {
        return "<div>TODO: render image media</div>";
    }

    public static function class(): string
    {
        return 'image';
    }

    public static function className(): string
    {
        return 'Image';
    }

    public static function description(): string
    {
        return 'Upload a single image to embed or post as a download';
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
