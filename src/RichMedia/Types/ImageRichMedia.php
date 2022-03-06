<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\HTML\FIGURE;
use DigraphCMS\HTML\ResponsivePicture;
use DigraphCMS\RichContent\RichContent;
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
        $image = new ResponsivePicture(
            $media->file()->image(),
            $media['alt']
        );
        if ($media->caption()) {
            $figure = (new FIGURE)
                ->setAttribute('style', 'background-color:' . $media->file()->image()->color())
                ->addChild($image)
                ->addChild('<figcaption>' . $media->caption() . '</figcaption>');
            return $figure->toString();
        }
        return $image->toString();
    }

    public function setCaption(RichContent $caption = null)
    {
        unset($this['caption']);
        if ($caption) {
            $this['caption'] = $caption->array();
        }
    }

    public function caption(): ?RichContent
    {
        if ($this['caption']) {
            return new RichContent($this['caption']);
        }
        return null;
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
