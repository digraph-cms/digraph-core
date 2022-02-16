<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Pages;
use DigraphCMS\HTML\A;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\URL\WaybackMachine;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class BookmarkRichMedia extends AbstractRichMedia
{
    public static function class(): string
    {
        return 'bookmark';
    }

    public static function className(): string
    {
        return 'Bookmark';
    }

    public static function description(): string
    {
        return 'Reusable link to either an internal page or an external URL';
    }

    public static function shortCode(ShortcodeInterface $s): ?string
    {
        $bookmark = RichMedia::get($s->getBbCode());
        if ($bookmark instanceof BookmarkRichMedia) {
            $link = (new A)
                ->setAttribute('title', $bookmark->name());
            if ($bookmark['mode'] == 'url') {
                // arbitrary URL
                $url = $bookmark['url'];
                $link->setAttribute('href', $url);
                if (!WaybackMachine::check($url)) {
                    if ($wb = WaybackMachine::get($url, $bookmark->updated())) {
                        // Wayback Machine says URL is broken and found an archived copy
                        $link->setAttribute('href', $wb->helperURL())
                            ->addClass('link--wayback')
                            ->setAttribute('title', 'Wayback Machine: ' . $bookmark->name());
                    } else {
                        // broken URL but no archived copy found
                        $link->addClass('link--broken')
                            ->setAttribute('title', 'Link may be broken');
                    }
                }
            } elseif ($bookmark['mode'] == 'page') {
                // link to a page on this site
                if ($bookmark['page'] && $page = Pages::get($bookmark['page'])) {
                    $link->setAttribute('href', $page->url())
                        ->setAttribute('title', $page->name());
                } else {
                    $link->addClass('link--broken')
                        ->setAttribute('title', 'linked page is missing');
                }
            }
            $link
                ->addChild($s->getContent() ? $s->getContent() : $bookmark->name());
            return $link;
        }
        return null;
    }
}
