<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Config;
use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Text;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\WaybackMachine;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ShortCodesListener
{
    /**
     * Generic listener to automatically handle Rich Media tags based on their
     * name in Config rich_media_types list. Calls the class' shortCode static
     * method so that everything relating to a Rich Media type can be self
     * contained and automatically wired up.
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode(ShortcodeInterface $s): ?string
    {
        if ($class = Config::get('rich_media_types.'.$s->getName())) {
            return $class::shortCode($s);
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
            // set up URL
            $link = (new A)
                ->setAttribute('href', $url)
                ->addChild($s->getContent() ? $s->getContent() : preg_replace('/^(https?:)?\/\//', '', $url));
            // check in wayback machine
            if (!WaybackMachine::check($url)) {
                if ($wb = WaybackMachine::get($url)) {
                    // Wayback Machine says URL is broken and found an archived copy
                    $link->setAttribute('href', $wb->helperURL())
                        ->addClass('link--wayback')
                        ->setAttribute('title', 'Wayback Machine: ' . $url);
                } else {
                    // broken URL but no archived copy found
                    $link->addClass('link--broken')
                        ->setAttribute('title', 'Link may be broken');
                }
            }
            // return built link
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
}
