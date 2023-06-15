<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Config;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Router;
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

    public static function onShortCode_page_created_by(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode()) ?? Context::page();
        if (!$page) return '<span class="notification notification--error">no page</span>';
        return $page->createdBy();
    }

    public static function onShortCode_page_updated_by(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode()) ?? Context::page();
        if (!$page) return '<span class="notification notification--error">no page</span>';
        return $page->updatedBy();
    }

    public static function onShortCode_page_created(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode()) ?? Context::page();
        if (!$page) return '<span class="notification notification--error">no page</span>';
        return Format::datetime($page->created());
    }

    public static function onShortCode_page_updated(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode()) ?? Context::page();
        if (!$page) return '<span class="notification notification--error">no page</span>';
        return Format::datetime($page->updated());
    }

    public static function onShortCode_page_created_date(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode()) ?? Context::page();
        if (!$page) return '<span class="notification notification--error">no page</span>';
        return Format::date($page->created());
    }

    public static function onShortCode_page_updated_date(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode()) ?? Context::page();
        if (!$page) return '<span class="notification notification--error">no page</span>';
        return Format::date($page->updated());
    }

    public static function onShortCode_page_name(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode()) ?? Context::page();
        if (!$page) return '<span class="notification notification--error">no page</span>';
        return $page->name();
    }

    public static function onShortCode_video(ShortcodeInterface $s): ?string
    {
        return VideoEmbed::fromURL(trim($s->getContent()));
    }

    public static function onShortCode_toc(ShortcodeInterface $s): ?string
    {
        $page = Pages::get($s->getBbCode() ?? Context::pageUUID());
        if (!$page) return null;
        $toc = new TableOfContents($page, intval($s->getParameter('depth', '1')));
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
                ->addClassString($s->getParameter('class', ''))
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
     * Handle link shortcodes, which can be to either pages or static routes
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode_link(ShortcodeInterface $s): ?string
    {
        try {
            // try to parse given URL, or use context if not specified
            if (!$s->getBbCode()) $url = Context::url();
            else {
                $url = $s->getBbCode();
                if (!str_starts_with($url, '/')) $url = "/$url";
                $url = new URL($url);
            }
            // try to use the given action
            if ($s->getParameter('action')) {
                $url->setAction($s->getParameter('action'));
            }
        } catch (\Throwable $th) {
            // if there was a problem, abort displaying this tag
            return null;
        }
        // track whether there are multiple options
        $multiple_options = false;
        // search for pages this URL might refer to
        if (!$url->explicitlyStaticRoute() && $pages = Pages::getAll($url->route())) {
            // this route relates to one or more pages
            $route = $url->route();
            $action = $url->action();
            if (count($pages) == 1 && !Router::staticRouteExists($route, $action)) {
                // one page with no matching static route: use its name as default name
                $title = $pages[0]->url($action)->name();
            } else {
                // there are multiple options
                $multiple_options = true;
                $title = array_map(fn($page) => $page->url($action)->name(), $pages);
                if (Router::staticRouteExists($route, $action)) $title[] = (new URL("/$route/"))->setAction($action)->name();
                $title = sprintf('{multiple options: %s}', implode(', ', $title));
            }
        } else {
            // this URL is static, it refers to no pages
            $title = $url->name();
        }
        $link = (new A)
            ->setAttribute('href', $url)
            ->setAttribute('title', $title)
            ->addClassString($s->getParameter('class', ''))
            ->addChild(new Text($s->getContent() ? $s->getContent() : $title));
        if ($multiple_options) $link->addClass('link--multiple-options');
        return $link;
    }
}