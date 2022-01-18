<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Content\Pages;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Text;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\FileRichMedia;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ShortCodesListener
{
    /**
     * Handle page link shortcodes
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode_link(ShortcodeInterface $s): ?string
    {
        if ($page = Pages::get($s->getBbCode())) {
            return (new A())
                ->setAttribute('href', $page->url())
                ->setAttribute('title', $page->name())
                ->addChild(new Text($s->getContent() ? $s->getContent() : $page->name()));
        } else {
            return null;
        }
    }

    public static function onShortCode_file(ShortcodeInterface $s): ?string
    {
        $file = RichMedia::get($s->getBbCode());
        if ($file instanceof FileRichMedia) {
            // return 'test';
            return $file->card();
        }
        return 'null';
    }
}
