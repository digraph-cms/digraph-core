<?php

namespace DigraphCMS;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Slugs;
use DigraphCMS\DOM\CodeHighlighter;
use DigraphCMS\DOM\DOM;
use DigraphCMS\DOM\DOMEvent;
use DigraphCMS\HTTP\Response;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

use function DigraphCMS\Content\require_file;

class CoreEventSubscriber
{
    /**
     * Construct a card for displaying rich media in autocomplete fields
     *
     * @param AbstractRichMedia $media
     * @param string $query
     * @return void
     */
    public static function onRichMediaAutocompleteCard(AbstractRichMedia $media, string $query)
    {
        $page = $media->pageUUID() ? Pages::get($media->pageUUID()) : null;
        return [
            'html' => '<div class="title">' . $media->name() . '</div><div class="meta">' . ($page ? $page->name() : '') . '</div><div class="meta">' . Format::datetime($media->updated()) . '</div>',
            'value' => $media->uuid(),
            'class' => 'rich-media',
            'extra' => [
                'tag' => $media->defaultTag()
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
            'rich_media_uuid = ?',
            [$media->uuid()]
        );
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
     * @return void
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
     * @return void
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
            DOM::html($response->content(), true)
        );
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
     * @param Page $page
     * @return void
     */
    public static function onAfterPageInsert(Page $page)
    {
        Slugs::setFromPattern($page, $page->slugPattern(), true);
    }

    /**
     * Update slug pattern after a page is updated.
     *
     * @param Page $page
     * @return void
     */
    public static function onAfterPageUpdate(Page $page)
    {
        Slugs::setFromPattern($page, $page->slugPattern(), true);
    }

    /**
     * Build a card for a page in the results of an autocomplete field.
     *
     * @param Page $page
     * @param string|null $query
     * @return array
     */
    public static function onPageAutocompleteCard(Page $page, string $query = null): array
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
     * Score how well a page matches a given query.
     *
     * @param Page $page
     * @param string $query
     * @return void
     */
    public static function onScorePageResult(Page $page, string $query)
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
     * Limits access to ~admin routes to admin users
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
     * Limits access to ~user route to signed-in users only, unless user is 
     * specified in an arg, in which case it limits to that user or admins
     *
     * @param URL $url
     * @param User $user
     * @return boolean|null
     */
    public static function onStaticUrlPermissions_user(URL $url, User $user): ?bool
    {
        // disable authentication log if php sessions are being used
        if (Config::get('php_session.enabled') && $url->path() == '/~user/settings/_authentication_log.html') {
            return false;
        }
        // if user is specified limit routes for the user being viewed/edited and admins
        if ($url->arg('user')) {
            return
                $url->arg('user') == $user->uuid()
                || Permissions::inMetaGroup('users__admin', $user);
        }
        // otherwise return whether this is a user
        return Permissions::inGroup('users', $user);
    }

    /**
     * Assign user profile page as the parent URL of user management pages
     *
     * @param URL $url
     * @return URL|null
     */
    public static function onStaticUrlParent_user(URL $url): ?URL
    {
        if ($url->arg('user') && $user = Users::get($url->arg('user'))) {
            return $user->profile();
        }
        return Users::get(Session::user())->profile();
    }

    /**
     * Limits access to ~groups route to user editors
     *
     * @param URL $url
     * @param User $user
     * @return boolean|null
     */
    public static function onStaticUrlPermissions_groups(URL $url, User $user): ?bool
    {
        return Permissions::inMetaGroup('users__edit');
    }

    /**
     * Limits access to ~users route to user editors
     *
     * @param URL $url
     * @param User $user
     * @return boolean|null
     */
    public static function onStaticUrlPermissions_users(URL $url, User $user): ?bool
    {
        // limit guest profile to editors and admins
        if ($url->action() == '_guest') {
            return Permissions::inMetaGroup('users__admin');
        }
        // limit other users to their own profile, unless given users__edit or users__view
        return $url == $user->profile() || Permissions::inMetaGroups([
            'users__edit',
            'users__view'
        ]);
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
        return 'Wayback Machine';
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
     * Add extra links to user action menu
     *
     * @param array $urls
     * @return void
     */
    public static function onStaticActions_user(array &$urls)
    {
        $urls[] = new URL('/~color/');
        $urls[] = new URL('/~admin/');
    }

    public static function onStaticActions_guest(array &$urls)
    {
        $urls[] = new URL('/~color/');
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
