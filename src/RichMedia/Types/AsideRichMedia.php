<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\HTML\ASIDE;
use DigraphCMS\RichContent\RichContent;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class AsideRichMedia extends AbstractRichMedia
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
        return 'aside';
    }

    public static function className(): string
    {
        return 'Aside';
    }

    public static function description(): string
    {
        return 'A block of customizable content contained in a box';
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
        $aside = (new ASIDE)
            ->addChild($media->content()->html())
            ->addClass('aside-media')
            ->setID('aside-' . $media->uuid());
        if ($code->getParameter('block','false') == 'true') {
            $aside->addClass('aside--block');
        }
        return $aside;
    }
}
