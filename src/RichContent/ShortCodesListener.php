<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Config;
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
        $output = sprintf('<a href="mailto:%s">%s</a>', $email, $content);
        if (Context::fields()['simplified_rendering']) return $output;
        else return Format::base64obfuscate($output);
    }

    /**
     * Handle link shortcodes, which can be to either pages or static routes
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    public static function onShortCode_link(ShortcodeInterface $s): ?string
    {
        if (!$s->getBbCode()) {
            // nothing is specified, just use context URL route and optional action
            $url = Context::url();
            $route = Context::url()->route();
            $action = $s->getParameter('action', 'index');
        } else {
            // otherwise we have to take a closer look at what was specified
            // check if bbcode value looks like it has a filename at the end
            $query = trim($s->getBbCode(), '/');
            if (preg_match('@\.[a-z0-9]+$@', $query)) {
                // route is bbcode value with filename stripped
                $route = preg_replace('@[^\\]\.([a-z0-9]+$@', '', $query);
                $action = substr($query, strlen($route) + 1);
                // still allow action parameter to override URL
                $action = $s->getParameter('action', $action);
                $url = new URL("/$route/$action");
            } else {
                // route looks like a clean route with no filename
                $route = $query;
                $action = $s->getParameter('action', 'index');
                $url = new URL("/$route/");
            }
        }
        // keep running list of everything this could refer to
        /** array<int,array{url:URL,title:string}> */
        $options = [];
        // check if there's a static route
        if (Router::staticRouteExists($route, $action)) {
            $static_url = new URL("/~$route/");
            $static_url->setAction($action);
            $options[] = [
                'url' => $static_url,
                'title' => $static_url->name(),
            ];
        }
        // search for pages this URL might refer to
        foreach (Pages::getAll($route) as $page) {
            $page_url = $page->url($action);
            $options[] = [
                'url' => $page_url,
                'title' => $page_url->name(),
            ];
        }
        // prepare link title
        if (count($options) == 0) {
            $title = 'unknown link';
        } elseif (count($options) == 1) {
            $title = $options[0]['title'];
            $url = $options[0]['url'];
        } else {
            $title = sprintf(
                'ambiguous link: %s',
                implode(
                    ', ',
                    array_map(
                        fn ($e) => '"' . $e['title'] . '"',
                        $options
                    )
                )
            );
        }
        // add fragment to URL
        $fragment = trim($s->getParameter('fragment', ''));
        if ($fragment) $fragment = '#' . $fragment;
        // build link
        $link = (new A)
            ->setAttribute('href', $url . $fragment)
            ->setAttribute('title', $title)
            ->addClassString($s->getParameter('class', ''))
            ->addChild(new Text($s->getContent() ? $s->getContent() : $title));
        if (count($options) > 1) $link->addClass('link--multiple-options');
        return $link;
    }
}
