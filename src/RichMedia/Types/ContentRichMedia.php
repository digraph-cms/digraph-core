<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\RichContent\RichContent;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ContentRichMedia extends AbstractRichMedia
{

    public function content(RichContent $set = null): RichContent
    {
        if ($set) {
            $this['content'] = $set->array();
        }
        return new RichContent($this['content']);
    }

    public static function class(): string
    {
        return 'content';
    }

    public static function className(): string
    {
        return 'Content';
    }

    public static function description(): string
    {
        return 'A block of customizable content that is seamlessly embedded wherever it is placed';
    }

    /**
     * Generate a shortcode rendering of this media
     *
     * @param ShortcodeInterface $code
     * @param self $media
     * @return string|null
     */
    public static function shortCode(ShortcodeInterface $code, $media): ?string
    {
        return $media->content()->html();
    }
}
