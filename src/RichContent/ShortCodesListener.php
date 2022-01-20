<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\Pages;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Text;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\FileRichMedia;
use DigraphCMS\UI\Format;
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
            return (new A)
                ->setAttribute('href', $page->url())
                ->setAttribute('title', $page->name())
                ->addChild(new Text($s->getContent() ? $s->getContent() : $page->name()));
        } else {
            return null;
        }
    }

    /**
     * insert a file rich media, as a card by default, but will be converted to
     * an inline link if the inline attribute is set, or if it has content.
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode_file(ShortcodeInterface $s): ?string
    {
        $media = RichMedia::get($s->getBbCode());
        if ($media instanceof FileRichMedia) {
            if ($s->getParameter('inline') || $s->getContent()) {
                return (new A)
                    ->setAttribute('href', $media->file()->url())
                    ->setAttribute('title', $media->file()->filename() . ' (' . Format::filesize($media->file()->bytes()) . ')')
                    ->addChild($s->getContent() ?? $media->file()->filename());
            } else {
                return $media->card();
            }
        }
        return null;
    }

    /**
     * Convert m codes to mark tags, optionally adding a class with the bbcode
     *
     * @param ShortcodeInterface $s
     * @return string
     */
    public static function onShortCode_m(ShortcodeInterface $s): string
    {
        $class = '';
        if ($c = $s->getBbCode()) {
            if (preg_match('/^[a-z]+$/', $c)) {
                $class = ' class="mark--' . $c . '"';
            }
        }
        return sprintf('<mark%s>%s</mark>', $class, $s->getContent());
    }
}
