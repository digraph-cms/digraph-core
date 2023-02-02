<?php

namespace DigraphCMS;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Slugs;
use DigraphCMS\DOM\CodeHighlighter;
use DigraphCMS\DOM\DOM;
use DigraphCMS\DOM\DOMEvent;
use DigraphCMS\HTTP\Response;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\MenuBar\MenuItem;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\URL\WaybackMachine;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use DOMComment;
use DOMElement;

use function DigraphCMS\Content\require_file;

abstract class CoreEventSubscriber
{

    /**
     * Preserve/enforce "id" argument in actions across the users/profile route
     *
     * @param ActionMenu $menu
     * @return void
     */
    public static function onActionMenu_users_profile(ActionMenu $menu)
    {
        foreach ($menu->children() as $item) {
            if ($item instanceof MenuItem) {
                $url = $item->url();
                if ($url && $url->route() == 'users/profile') {
                    $url->arg('id', Context::arg('id') ?? Session::user());
                    $menu->removeChild($item);
                    $new = $menu->addURL($url, $item->label());
                    foreach ($item->classes() as $class) {
                        $new->addClass($class);
                    }
                }
            }
        }
    }

    /**
     * Construct a card for displaying rich media in autocomplete fields
     *
     * @param AbstractRichMedia $media
     * @param string $query
     * @return array<string,mixed>
     */
    public static function onRichMediaAutocompleteCard(AbstractRichMedia $media, string $query)
    {
        $page = $media->parent() ? Pages::get($media->parent()) : null;
        return [
            'html' => '<div class="title">' . $media->icon() . ' ' . $media->name() . '</div><div class="meta">' . ($page ? $page->name() : '') . '</div><div class="meta">' . Format::datetime($media->updated()) . '</div>',
            'value' => $media->uuid(),
            'class' => 'rich-media',
            'extra' => [
                'tag' => $media->defaultTag(),
                'wrappingTag' => $media->defaultWrappingTag()
            ]
        ];
    }

    /**
     * When Rich Media is deleted, delete all Filestore files associated with it
     *
     * @param AbstractRichMedia $media
     * @return void
     */
    public static function onAfterRichMediaDelete(AbstractRichMedia $media)
    {
        $files = Filestore::select()->where(
            'parent = ?',
            [$media->uuid()]
        );
        /** @var FilestoreFile $file */
        foreach ($files as $file) {
            $file->delete();
        }
    }

    /**
     * Render PHP route files by including the file using the function in the
     * Content namespace.
     *
     * @param string $file
     * @param string $route
     * @return string
     */
    public static function onRenderRoute_php(string $file, string $route)
    {
        return require_file($file);
    }

    /**
     * Render markdown route files by handling them as RichContent, which means
     * that ShortCode tags also work when you do this.
     *
     * @param string $file
     * @param string $route
     * @return string
     */
    public static function onRenderRoute_md(string $file, string $route)
    {
        return (new RichContent(file_get_contents($file)))
            ->html();
    }

    /**
     * Before wrapping a response in a template, process it with the DOM helper
     * to dispatch events for all of its DOM elements.
     *
     * @param Response $response
     * @return void
     */
    public static function onTemplateWrapResponse(Response $response)
    {
        $response->content(
            DOM::html($response->content())
        );
    }

    public static function onDOMComment(DOMEvent $e)
    {
        /** @var DOMComment */
        $comment = trim($e->getNode()->textContent);
        switch ($comment) {
            case 'wayback-disable-notifications':
                WaybackMachine::disableNotifications();
                break;
            case 'wayback-enable-notifications':
                WaybackMachine::enableNotifications();
                break;
            case 'wayback-disable':
                WaybackMachine::deactivate();
                break;
            case 'wayback-enable':
                WaybackMachine::activate();
                break;
        }
    }

    public static function onDOMElement_a(DOMEvent $e)
    {
        static $site;
        $site = $site ?? preg_replace('@^(https?:)?//@', '//', URLs::site());
        /** @var DOMElement */
        $node = $e->getNode();
        $href = $node->getAttribute('href');
        if (!$href) return;
        if ($node->getAttribute('data-wayback-ignore')) return;
        if (!preg_match('/^https?:\/\//', $href)) return;
        $normalizedURL = preg_replace('@^(https?:)?//@', '//', $href);
        if (substr($normalizedURL, 0, strlen($site)) != $site) {
            if (!WaybackMachine::check($href)) {
                if ($wb = WaybackMachine::get($href)) {
                    // Wayback Machine says URL is broken and found an archived copy
                    $node->setAttribute('href', $wb->helperURL());
                    $node->setAttribute('data-link-wayback', 'true');
                    $node->setAttribute('title', 'Wayback Machine: ' . $href);
                } else {
                    // broken URL but no archived copy found
                    $node->setAttribute('data-link-broken', 'true');
                    $node->setAttribute('title', 'This link may be broken');
                }
            }
        }
    }

    /**
     * Highlight code in CODE tags
     *
     * @param DOMEvent $e
     * @return void
     */
    public static function onDOMElement_code(DOMEvent $e)
    {
        CodeHighlighter::codeEvent($e);
    }

    /**
     * Set initial slug pattern after a page is inserted.
     *
     * @param AbstractPage $page
     * @return void
     */
    public static function onAfterPageInsert(AbstractPage $page)
    {
        Slugs::setFromPattern($page, $page->slugPattern());
    }

    /**
     * Update slug pattern after a page is updated.
     *
     * @param AbstractPage $page
     * @return void
     */
    public static function onAfterPageUpdate(AbstractPage $page)
    {
        Slugs::setFromPattern($page, $page->slugPattern());
    }

    /**
     * Build a card for a page in the results of an autocomplete field.
     *
     * @param AbstractPage $page
     * @param string|null $query
     * @return array
     */
    public static function onPageAutocompleteCard(AbstractPage $page, string $query = null): array
    {
        $name = $page->name();
        $url = $page->url();
        if ($query) {
            $words = preg_split('/ +/', trim($query));
            foreach ($words as $word) {
                $word = preg_quote($word);
                $name = preg_replace('/' . $word . '/i', '<strong>$0</strong>', $name);
            }
        }
        return [
            'html' => '<div class="title">' . $name . '</div><div class="url">' . $url . '</div>',
            'value' => $page->uuid(),
            'class' => 'page'
        ];
    }

    /**
     * Build a card for a page in the results of an autocomplete field.
     *
     * @param User $user
     * @param string|null $query
     * @return array
     */
    public static function onUserAutoCompleteCard(User $user, string $query = null): array
    {
        $name = $user->name();
        if ($query) {
            $words = preg_split('/ +/', trim($query));
            foreach ($words as $word) {
                $word = preg_quote($word);
                $name = preg_replace('/' . $word . '/i', '<strong>$0</strong>', $name);
            }
        }
        return [
            'html' => '<div class="title">' . $name . '</div><small class="date">' . Format::date($user->created()) . '</small>',
            'value' => $user->uuid(),
            'class' => 'user'
        ];
    }

    /**
     * Score how well a page matches a given query.
     *
     * @param AbstractPage $page
     * @param string $query
     * @return int
     */
    public static function onScorePageResult(AbstractPage $page, string $query)
    {
        $query = strtolower($query);
        $score = 0;
        if ($page->uuid() == $query || $page->slug() == $query) {
            $score += 100;
        }
        $score += similar_text(metaphone($query), metaphone($page->name()));
        return $score;
    }

    /**
     * Limits access to non-wildcard wayback routes
     *
     * @param URL $url
     * @param User $user
     * @return boolean|null
     */
    public static function onStaticUrlPermissions_wayback(URL $url, User $user): ?bool
    {
        if ($url->actionPrefix() == 'page') return true;
        else return Permissions::inMetaGroup('wayback__edit', $user);
    }

    /**
     * Limits access to ~admin routes
     *
     * @param URL $url
     * @param User $user
     * @return boolean|null
     */
    public static function onStaticUrlPermissions_admin(URL $url, User $user): ?bool
    {
        return Permissions::inGroup('admins', $user);
    }

    /**
     * Limits access to ~richmedia routes
     *
     * @param URL $url
     * @param User $user
     * @return boolean|null
     */
    public static function onStaticUrlPermissions_richmedia(URL $url, User $user): ?bool
    {
        return Permissions::inMetaGroup('richmedia__edit', $user);
    }

    /**
     * @param URL $url
     * @param User $user
     * @return boolean|null
     */
    public static function onStaticUrlPermissions_users(URL $url, User $user): ?bool
    {
        // disable authentication log if php sessions are being used
        if (Config::get('php_session.enabled') && $url->path() == '/~users/profile/authentication_log.html') {
            return false;
        }
        // limit list to users__view
        if ($url->route() == 'users') return Permissions::inMetaGroup('users__view', $user);
        // if user is specified limit routes for the user being viewed/edited and admins
        if ($url->route() == 'users/profile' && $url->arg('id')) {
            if ($url->action() == 'index') {
                // viewing profiles set to users__view
                return ($url->arg('id') == $user->uuid() && $user->uuid() != 'guest')
                    || Permissions::inMetaGroup('users__view', $user);
            } else {
                // everything else limited to users__admin
                return ($url->arg('id') == $user->uuid() && $user->uuid() != 'guest')
                    || Permissions::inMetaGroup('users__admin', $user);
            }
        }
        // otherwise return whether this is a user
        return Permissions::inGroup('users', $user);
    }

    /**
     * URL names for unsubscribe routes
     *
     * @param URL $url
     * @return string|null
     */
    public static function onStaticUrlName_unsubscribe(URL $url): ?string
    {
        if ($url->action() == 'index') {
            return 'Manage email preferences';
        }
        return 'Unsubscribe';
    }

    /**
     * Set URL name of user profiles
     *
     * @param URL $url
     * @return string|null
     */
    public static function onStaticUrlName_users_profile(URL $url): ?string
    {
        if ($url->action() == 'index') {
            $user = null;
            if ($url->arg('id') && $user = Users::get($url->arg('id'))) {
                $user = $user;
            } elseif (!$url->arg('id')) {
                $user = Users::current() ?? Users::guest();
            }
            if ($user) {
                if ($user == Users::current()) return "My profile";
                else return $user->name();
            }
        }
        return null;
    }

    /**
     * Set URL parent of user profile pages
     *
     * @param URL $url
     * @return URL|null
     */
    public static function onStaticUrlParent_users_profile(URL $url): ?URL
    {
        if ($url->action() != 'index' && $url->arg('id')) {
            return new URL('/~users/profile/?id=' . $url->arg('id'));
        }
        return null;
    }

    /**
     * Make the name of group URLs the group's name
     *
     * @param URL $url
     * @param boolean $inPageContext
     * @return string|null
     */
    public static function onStaticUrlName_groups(URL $url, bool $inPageContext): ?string
    {
        if ($group = Users::group($url->action())) {
            return $group->name();
        }
        return null;
    }

    /**
     * Name wayback machine helper URLs
     *
     * @param URL $url
     * @param boolean $inPageContext
     * @return string|null
     */
    public static function onStaticUrlName_wayback(URL $url, bool $inPageContext): ?string
    {
        if ($url->actionPrefix() == 'page') return 'Wayback link';
        else return null;
    }

    /**
     * Name color settings URL
     *
     * @param URL $url
     * @param boolean $inPageContext
     * @return string|null
     */
    public static function onStaticUrlName_color(URL $url, bool $inPageContext): ?string
    {
        return 'Color settings';
    }

    /**
     * Make the name of user profile URLs the user's name
     *
     * @param URL $url
     * @param boolean $inPageContext
     * @return string|null
     */
    public static function onStaticUrlName_users(URL $url, bool $inPageContext): ?string
    {
        if ($user = Users::get($url->action())) {
            return $user->name();
        }
        return null;
    }

    /**
     * Name for signin page
     *
     * @return string|null
     */
    public static function onStaticUrlName_signin(): ?string
    {
        return "Log in";
    }

    /**
     * Name for signout page
     *
     * @return string|null
     */
    public static function onStaticUrlName_signout(): ?string
    {
        return "Log out";
    }

    /**
     * Remove all static actions from signin path
     *
     * @param URL[] $urls
     * @return void
     */
    public static function onStaticActions_signin(array &$urls)
    {
        $urls = [];
    }

    /**
     * Remove all static actions from signout path
     *
     * @param URL[] $urls
     * @return void
     */
    public static function onStaticActions_signout(array &$urls)
    {
        $urls = [];
    }
}
