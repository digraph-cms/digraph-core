<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Config;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Text;
use DigraphCMS\RichContent\Video\VideoEmbed;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\TableOfContents;
use DigraphCMS\URL\URL;
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
        if ($class = Config::get('rich_media_types.' . $s->getName())) {
            if (($media = RichMedia::get($s->getBbCode())) instanceof $class) {
                return $media->shortCode($s);
            }
        }
        return null;
    }

    public static function onShortCode_video(ShortcodeInterface $s): ?string
    {
        return VideoEmbed::fromURL(trim($s->getContent()));
    }

    public static function onShortCode_toc(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode() ?? Context::pageUUID());
        if (!$page) return null;
        $toc = new TableOfContents($page, intval($s->getParameter('depth', 1)));
        return $toc->__toString();
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
            // return built link
            return $link;
        }
        return null;
    }

    /**
     * Obfuscate a link to an email address
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode_email(ShortcodeInterface $s): ?string
    {
        $email = $s->getBbCode() ?? $s->getContent();
        $content = $s->getContent() ? $s->getContent() : $email;
        return Format::base64obfuscate(sprintf('<a href="mailto:%s">%s</a>', $email, $content));
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
                    function (AbstractPage $page): string {
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
