<?php

namespace DigraphCMS\Content;

use ArrayAccess;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Cron\CronJob;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\Cron\RecursivePageJob;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\Search\Search;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Flatrr\FlatArrayTrait;
use Throwable;

abstract class AbstractPage implements ArrayAccess
{
    use FlatArrayTrait {
        set as protected rawSet;
        unset as protected rawUnset;
    }

    const DEFAULT_SLUG = '[name]';
    const DEFAULT_UNIQUE_SLUG = true;
    const ORDER_IGNORES_WEIGHT = false;
    const ORDER_USES_SORT_NAME = true;

    protected $uuid, $name, $sortName;
    protected $sortWeight = 0;
    protected $slug = false;
    protected $created, $created_by;
    protected $updated, $updated_by;
    protected $updated_last;
    protected $slugCollisions;
    protected static $class;
    protected $slugPattern;

    abstract public function richContent(string $index, RichContent $content = null): ?RichContent;
    abstract public function allRichContent(): array;

    public function __construct(array $data = [], array $metadata = [])
    {
        $this->uuid = @$metadata['uuid'] ?? Digraph::uuid();
        $this->name = @$metadata['name'] ?? 'Untitled';
        $this->created = @$metadata['created'] ?? new DateTime();
        $this->created_by = @$metadata['created_by'] ?? Session::uuid();
        $this->updated = @$metadata['updated'] ?? new DateTime();
        $this->updated_last = clone $this->updated;
        $this->updated_by = @$metadata['updated_by'] ?? Session::uuid();
        $this->rawSet(null, $data);
        $this->slugPattern = @$metadata['slug_pattern'] ?? static::DEFAULT_SLUG;
    }

    public static function onRecursiveDelete(DeferredJob $job, AbstractPage $page)
    {
        // does nothing, but can be extended
    }

    protected function _date(string $key): ?DateTime
    {
        if ($this[$key]) {
            return DateTime::createFromFormat('Y-m-d', $this[$key], Theme::timezone());
        }
        return null;
    }

    protected function _setDate(string $key, DateTime $date)
    {
        $this[$key] = $date->format('Y-m-d');
    }

    protected function _datetime(string $key): ?DateTime
    {
        if ($this[$key]) {
            return DateTime::createFromFormat('U', $this[$key], Theme::timezone());
        }
        return null;
    }

    protected function _setDatetime(string $key, DateTime $date)
    {
        $this[$key] = $date->getTimestamp();
    }

    public function metadata(): array
    {
        $data = [
            'Created' => sprintf('%s by %s', Format::datetime($this->created()), $this->createdBy()),
            'Last modified' => sprintf('%s by %s', Format::datetime($this->updated()), $this->updatedBy()),
            'Type' => $this->class(),
            'UUID' => '<code>' . $this->uuid() . '</code>',
            'URLs' => array_map(
                function (string $slug) {
                    $url = new URL("/$slug/");
                    return "<a href='$url'>$slug</a>";
                },
                Slugs::list($this->uuid())
            )
        ];
        if ($this['copied_from']) {
            if ($page = Pages::get($this['copied_from'])) {
                $data['Copied from'] = $page->url()->html();
            } else {
                $data['Copied from'] = '<em>Deleted page <code>' . $this['copied_from'] . '</code></em>';
            }
        }
        Dispatcher::dispatchEvent('onPageMetadata', [$this, &$data]);
        Dispatcher::dispatchEvent('onPageMetadata_' . $this->class(), [$this, &$data]);
        return $data;
    }

    public function addableTypes(): array
    {
        return array_filter(
            array_keys(Config::get('page_types')),
            function (string $type) {
                return Router::pageRouteExists($this, '_add_' . $type);
            }
        );
    }

    public function url_add(string $type): URL
    {
        return $this->url('_add_' . $type, ['uuid' => Digraph::uuid()]);
    }

    public function url_edit(): URL
    {
        return $this->url('edit');
    }

    public function slugVariable(string $name): ?string
    {
        switch ($name) {
            case 'uuid':
                return $this->uuid();
            case 'name':
                return $this->name();
            default:
                return null;
        }
    }

    public function parentPage(): ?AbstractPage
    {
        return Graph::parents($this->uuid(), 'normal')
            ->limit(1)
            ->fetch();
    }

    public function parent(URL $url = null): ?URL
    {
        if (!$url || $url->action() == 'index') {
            if ($parent = $this->parentPage()) {
                return $parent->url();
            } else {
                return null;
            }
        } else {
            return $this->url();
        }
    }

    public function slugPattern(string $slugPattern = null): ?string
    {
        if ($slugPattern && Slugs::validatePattern($this, $slugPattern)) {
            $this->slugPattern = $slugPattern;
        }
        return $this->slugPattern;
    }

    public function slug(): ?string
    {
        if ($this->slug === false) {
            $this->slug = @DB::query()->from('page_slug')
                ->where('page_uuid = ?', [$this->uuid()])
                ->order('id desc')
                ->limit(1)
                ->fetch()['url'];
        }
        return $this->slug ?? $this->uuid;
    }

    /**
     * Pages may override all other permissions for their own URLs. By default
     * they return null, which allows other permissions checks to be run.
     *
     * @param URL $url
     * @param User|null $user
     * @return boolean|null
     */
    public function permissions(URL $url, User $user = null): ?bool
    {
        if ($url->action() == 'index') {
            return true;
        } else {
            return Permissions::inMetaGroup('content__edit', $user);
        }
        return null;
    }

    public function slugCollisions(): bool
    {
        if ($this->slugCollisions === null) {
            $this->slugCollisions = Slugs::collisions($this->slug());
        }
        return $this->slugCollisions;
    }

    public function routeClasses(): array
    {
        return ['_any'];
    }

    public function class(): string
    {
        static $classes = [];
        $class = get_class($this);
        return @$classes[$class] ?? $classes[$class] = static::getClass($class);
    }

    protected function getClass(string $thisClass): string
    {
        $thisClass = preg_replace('/^[^\\\]/', '\\\$0', $thisClass);
        foreach (Config::get('page_types') as $name => $class) {
            if ($class == $thisClass) return $name;
        }
        throw new \Exception("Page class $thisClass is not configured");
    }

    /**
     * Retrieve children of this page, as ordered and filtered in the preferred
     * way for this particular class/page. May be ordered or filtered different
     * than what comes out of Graph::children(), and should be used when
     * displaying most user-facing interfaces for the page.
     * 
     * For example, this method might change the default ordering to creation
     * date for a blog/news section, or updated date for a list of downloads.
     * 
     * The default AbstractPage behavior is to respect the sort order column,
     * and then sort by sort name falling back to normal name.
     *
     * @return PageSelect
     */
    public function children(): PageSelect
    {
        return Graph::children($this->uuid(), null, static::ORDER_IGNORES_WEIGHT)
            ->order('COALESCE(sort_name, name) ASC');
    }

    public function name(string $name = null, bool $unfiltered = false, bool $forDB = false): string
    {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($unfiltered || $forDB) {
            return $this->name;
        } else {
            return htmlentities($this->name);
        }
    }

    public function sortName(): ?string
    {
        return $this->sortName ? $this->sortName : null;
    }

    public function sortWeight(): int
    {
        return $this->sortWeight;
    }

    public function setSortName(?string $name)
    {
        $this->sortName = strip_tags($name);
        return $this;
    }

    /**
     * Set the sorting weight of this page. Default is zero, and the defaults
     * used elsewhere are "extra sticky" = -200, "sticky" = -100, and "heavy" = 100
     *
     * @param integer|null $weight
     * @return void
     */
    public function setSortWeight(?int $weight)
    {
        $this->sortWeight = $weight ?? 0;
        return $this;
    }

    /**
     * Produce a title for a given URL, which may vary depending on whether
     * $inPageContext has been flagged to indicate that which page this URL
     * belongs to should be clear from the use's surrounding context.
     *
     * @param URL $url
     * @param boolean $inPageContext whether page name is obvious from context and should be omitted
     * @return ?string
     */
    public function title(URL $url = null, bool $inPageContext = false): ?string
    {
        if ($url && $url->action() == 'index') {
            return $this->name();
        } else {
            return null;
        }
    }

    /**
     * How long output may be cached internally to improve performance. Not
     * exposed in response headers.
     *
     * @param string $action
     * @return integer|null
     */
    public function cacheTTL(string $action): ?int
    {
        return null;
    }

    /**
     * How long output may be served by proxies even if it is otherwise stale,
     * or if there is an error. Used in max-stale and max-stale-error directives
     * in the cache-control response header.
     *
     * @param string $action
     * @return integer|null
     */
    public function staleTTL(string $action): ?int
    {
        return null;
    }

    /**
     * How long output should be cached by clients, using the max-age directive
     * of the cache-control response header.
     *
     * @param string $action
     * @return integer|null
     */
    public function browserTTL(string $action): ?int
    {
        return null;
    }

    public function url(string $action = null, array $args = null, bool $uuid = null): URL
    {
        if ($action && !preg_match('/\.[a-z0-9]+$/', $action)) {
            $action .= '.html';
        }
        if ($action == 'urls.html' || $action == 'links.html') {
            $uuid = true;
        }
        if ($uuid === true) {
            $slug = $this->uuid();
        } elseif ($uuid === false) {
            $slug = $this->slug();
        } else {
            $slug =
                ($this->slugCollisions() ?? Router::staticRouteExists($this->slug(), $action))
                ? $this->uuid()
                : $this->slug();
        }
        if ($slug == 'home' && $action != 'index.html' && $action != '') {
            $slug = $this->uuid();
        }
        try {
            $url = new URL("/$slug/$action");
        } catch (Throwable $th) {
            $uuid = $this->uuid();
            $url = new URL("/$uuid/$action");
        }
        $url->query($args);
        return $url;
    }

    public function prepareCronJobs(): bool
    {
        foreach (array_keys(Config::get('cron.intervals')) as $interval) {
            $method = "onCron_$interval";
            $uuid = $this->uuid();
            if (method_exists($this, $method)) {
                new CronJob(
                    'Page',
                    "$uuid::$method",
                    function (CronJob $job) use ($uuid, $method) {
                        static::runCronJob($job, $uuid, $method);
                    },
                    $interval
                );
            }
        }
        return true;
    }

    public function onCron_index_pages()
    {
        $body = $this->richContent('body');
        if ($body) Search::indexURL($this->uuid(), $this->url(), $this->name(), $body->html());
    }

    protected static function runCronJob(CronJob $job, string $uuid, string $method)
    {
        // pull page and delete the job if page or method one no longer exists
        $page = Pages::get($uuid);
        if (!$page || !is_callable([$page, $method])) $job->delete();
        // run method
        call_user_func([$page, $method], $job);
    }

    public function insert(string $parent_uuid = null)
    {
        $this->prepareCronJobs();
        return Pages::insert($this, $parent_uuid);
    }

    public function update()
    {
        $this->prepareCronJobs();
        return Pages::update($this);
    }

    public function delete()
    {
        return Pages::delete($this);
    }

    public function recursiveDelete(string $jobGroup = null): DeferredJob
    {
        return new RecursivePageJob(
            $this->uuid(),
            function (DeferredJob $job, AbstractPage $page) {
                $uuid = $page->uuid();
                // extensible recursive deletion
                $class = get_class($page);
                $class::onRecursiveDelete($job, $page);
                // queue deletion of all associated rich media
                $media = RichMedia::select($uuid);
                while ($m = $media->fetch()) {
                    $mUUID = $m->uuid();
                    $job->spawn(
                        function () use ($mUUID) {
                            $media = RichMedia::get($mUUID);
                            $media->delete();
                            return "Deleted rich media " . $media->name();
                        }
                    );
                }
                // queue deletion of this page last
                $job->spawn(
                    function () use ($uuid) {
                        // get page
                        $page = Pages::get($uuid);
                        if (!$page) return "Page $uuid already deleted";
                        // delete
                        $page->delete();
                        return "Deleted page " . $page->name() . " ($uuid)";
                    }
                );
                return "Queued page for deletion " . $page->name() . " ($uuid)";
            },
            true,
            $jobGroup
        );
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function setUUID(string $uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function createdBy(): User
    {
        return Users::user($this->created_by);
    }

    public function updatedBy(): User
    {
        return Users::user($this->updated_by);
    }

    public function createdByUUID(): string
    {
        return $this->created_by;
    }

    public function updatedByUUID(): string
    {
        return $this->updated_by;
    }

    public function created(): DateTime
    {
        return clone $this->created;
    }

    public function updated(): DateTime
    {
        return clone $this->updated;
    }

    public function updatedLast(): DateTime
    {
        return clone $this->updated_last;
    }
}
