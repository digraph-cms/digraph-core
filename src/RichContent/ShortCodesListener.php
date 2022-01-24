<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Text;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\BookmarkRichMedia;
use DigraphCMS\RichMedia\Types\FileRichMedia;
use DigraphCMS\RichMedia\Types\MultiFileRichMedia;
use DigraphCMS\RichMedia\Types\TableRichMedia;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ShortCodesListener
{

    public static function onShortCode_bookmark(ShortcodeInterface $s): ?string
    {
        $bookmark = RichMedia::get($s->getBbCode());
        if ($bookmark instanceof BookmarkRichMedia) {
            $link = new A;
            $title = $bookmark->name();
            if ($bookmark['mode'] == 'url') {
                $link->setAttribute('href', $bookmark['url']);
                // TODO: verify/wayback link
            } elseif ($bookmark['mode'] == 'page') {
                if ($page = Pages::get($bookmark['page'])) {
                    $link->setAttribute('href', $page->url());
                } else {
                    $link->addClass('link--broken');
                    $title = "$title (NOTE: linked page missing)";
                }
            }
            $link
                ->setAttribute('title', $title)
                ->addChild(new Text($s->getContent() ? $s->getContent() : $title));
            return $link;
        }
        return null;
    }
    /**
     * Shortcode for embedding rich media tables
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode_table(ShortcodeInterface $s): ?string
    {
        $table = RichMedia::get($s->getBbCode());
        if ($table instanceof TableRichMedia) {
            return $table->render();
        }
        return null;
    }

    /**
     * URL shortcodes for making links to arbitrary URLs
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode_url(ShortcodeInterface $s): ?string
    {
        $url = $s->getBbCode();
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $link = (new A)
                ->setAttribute('href', $url)
                ->addChild($s->getContent() ? $s->getContent() : preg_replace('/^(https?:)?\/\//', '', $url));
            // TODO: verify/wayback
            return $link;
        }
        return null;
    }

    /**
     * Handle page link shortcodes
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode_link(ShortcodeInterface $s): ?string
    {
        if ($pages = Pages::getAll($s->getBbCode())) {
            if (count($pages) == 1) {
                // if there is only one page at this slug/uuid, link to it
                $page = reset($pages);
                return (new A)
                    ->setAttribute('href', $page->url())
                    ->setAttribute('title', $page->name())
                    ->addChild(new Text($s->getContent() ? $s->getContent() : $page->name()));
            } else {
                // if there are multiple pages, give link link--multiple-options class
                // make default title indicate that there are multiple options
                $title = "Multiple options: " . implode(', ', array_map(
                    function (Page $page): string {
                        return $page->name();
                    },
                    $pages
                ));
                return (new A)
                    ->setAttribute('href', new URL('/' . $s->getBbCode() . '/'))
                    ->setAttribute('title', $title)
                    ->addClass('link--multiple-options')
                    ->addChild(new Text($s->getContent() ? $s->getContent() : $title));
            }
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
     * insert a file bundle rich media, as a card by default, but will be converted to
     * an inline link if the inline attribute is set, or if it has content.
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode_multifile(ShortcodeInterface $s): ?string
    {
        $media = RichMedia::get($s->getBbCode());
        if ($media instanceof MultiFileRichMedia) {
            if ($s->getParameter('inline') || $s->getContent()) {
                return (new A)
                    ->setAttribute('href', $media->zipFile()->url())
                    ->setAttribute('title', $media->zipFile()->filename())
                    ->addChild($s->getContent() ?? $media->zipFile()->filename());
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
