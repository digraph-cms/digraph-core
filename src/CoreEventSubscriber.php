<?php

namespace DigraphCMS;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Slugs;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\Cron\RecursivePageJob;
use DigraphCMS\DB\DB;
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
use DOMElement;

use function DigraphCMS\Content\require_file;

abstract class CoreEventSubscriber
{

    public static function cronJob_maintenance()
    {
        // expire deferred execution jobs
        new DeferredJob(
            function () {
                $count = DB::query()->delete('defex')
                    ->where('run is null')
                    ->where('run < ?', [strtotime(Config::get('maintenance.expire_defex_records'))])
                    ->execute();
                return "Expired $count deferred execution jobs";
            },
            'core_maintenance'
        );
        // expire locking records
        new DeferredJob(
            function () {
                $count = DB::query()->delete('locking')
                    ->where('expires < ?', [strtotime(Config::get('maintenance.expire_locking_records'))])
                    ->execute();
                return "Expired $count locking records";
            },
            'core_maintenance'
        );
        // expire cron errors
        new DeferredJob(
            function () {
                $count = DB::query()
                    ->update('cron', [
                        'error_time' => null,
                        'error_message' => null,
                    ])
                    ->where('error_time is not null')
                    ->where('error_time < ?', [strtotime(Config::get('maintenance.expire_cron_errors'))])
                    ->execute();
                return "Expired $count cron error messages";
            },
            'core_maintenance'
        );
        // expire search index records
        new DeferredJob(
            function () {
                $count = DB::query()->delete('search_index')
                    ->where('updated < ?', [strtotime(Config::get('maintenance.expire_search_index'))])
                    ->execute();
                return "Expired $count search index records";
            },
            'core_maintenance'
        );
    }

    public static function cronJob_maintenance_heavy()
    {
        // do periodic maintenance on all pages
        new DeferredJob(
            function (DeferredJob $job) {
                $pages = DB::query()
                    ->from('page')
                    ->leftJoin('page_link on end_page = page.uuid')
                    ->where('page_link.id is null');
                while ($page = $pages->fetch()) {
                    $uuid = $page['uuid'];
                    // recursive job to prepare cron jobs
                    new RecursivePageJob(
                        $uuid,
                        function (DeferredJob $job, AbstractPage $page) {
                            $count = $page->prepareCronJobs();
                            return sprintf("Prepared %s cron jobs for %s (%s)", $count, $page->name(), $page->uuid());
                        },
                        false,
                        $job->group()
                    );
                    // recursive job to refresh all slugs
                    new RecursivePageJob(
                        $uuid,
                        function (DeferredJob $job, AbstractPage $page) {
                            if (!$page->slugPattern()) return $page->uuid() . ": No slug pattern";
                            Slugs::setFromPattern($page, $page->slugPattern(), $page::DEFAULT_UNIQUE_SLUG);
                            return $page->uuid() . " slug set to " . $page->slug();
                        },
                        false,
                        $job->group()
                    );
                }
                return "Spawned page heavy maintenance jobs";
            },
            'core_maintenance_heavy'
        );
    }

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
                    $url->arg('user', Context::arg('id') ?? Session::user());
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
     * @return void
     */
    public static function onRichMediaAutocompleteCard(AbstractRichMedia $media, string $query)
    {
        $page = $media->parent() ? Pages::get($media->parent()) : null;
        return [
            'html' => '<div class="title">' . $media->name() . '</div><div class="meta">' . ($page ? $page->name() : '') . '</div><div class="meta">' . Format::datetime($media->updated()) . '</div>',
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
            DOM::html($response->content())
        );
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
                    $node->setAttribute('title', 'Link may be broken');
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
            'html' => '<div class="title">' . $name . '</div>',
            'value' => $user->uuid(),
            'class' => 'user'
        ];
    }

    /**
     * Score how well a page matches a given query.
     *
     * @param AbstractPage $page
     * @param string $query
     * @return void
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
        // if user is specified limit routes for the user being viewed/edited and admins
        if ($url->arg('id')) {
            if ($url->action() == 'index') {
                // viewing profiles set to users__view
                return
                    $url->arg('id') == $user->uuid()
                    || Permissions::inMetaGroup('users__view', $user);
            } else {
                // everything else limited to users__admin
                return
                    $url->arg('id') == $user->uuid()
                    || Permissions::inMetaGroup('users__admin', $user);
            }
        }
        // otherwise return whether this is a user
        return Permissions::inGroup('users');
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
            if ($url->arg('id') && $user = Users::get($url->arg('id'))) {
                $user = $user;
            } elseif (!$url->arg('id')) {
                $user = Users::current() ?? Users::guest();
            }
            if ($user) {
                if ($user == Users::current()) return 'My profile';
                else return $user->name();
            }
        }
        return null;
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
