<?php

namespace DigraphCMS\Content;

use ArrayAccess;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\UI\Format;
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

    protected $uuid, $name;
    protected $slug = false;
    protected $created, $created_by;
    protected $updated, $updated_by;
    protected $slugCollisions;
    protected static $class;

    abstract public function richContent(string $index, RichContent $content = null): ?RichContent;

    public function __construct(array $data = [], array $metadata = [])
    {
        $this->uuid = @$metadata['uuid'] ?? Digraph::uuid();
        $this->name = @$metadata['name'] ?? 'Untitled';
        $this->created = @$metadata['created'] ?? new DateTime();
        $this->created_by = @$metadata['created_by'];
        $this->updated = @$metadata['updated'] ?? new DateTime();
        $this->updated_last = clone $this->updated;
        $this->updated_by = @$metadata['updated_by'];
        $this->rawSet(null, $data);
        $this->changed = false;
        $this->slugPattern = @$metadata['slug_pattern'] ?? static::DEFAULT_SLUG;
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
                return true;
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
        return static::$class ?? static::$class = static::getClass();
    }

    protected static function getClass(): string
    {
        $thisClass = preg_replace('/^[^\\\]/','\\\$0',get_called_class());
        foreach (Config::get('page_types') as $name => $class) {
            if ($class == $thisClass) return $name;
        }
        throw new \Exception("Page class $thisClass is not configured");
    }

    public function name(string $name = null, bool $unfiltered = false): string
    {
        if ($name) {
            $this->name = $name;
        }
        if ($unfiltered) {
            return $this->name;
        } else {
            return htmlentities($this->name);
        }
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
        if ($action == 'urls.html') {
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

    public function insert()
    {
        return Pages::insert($this);
    }

    public function update()
    {
        return Pages::update($this);
    }

    public function delete()
    {
        return Pages::delete($this);
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
        return $this->created_by ? Users::user($this->created_by) : Users::guest();
    }

    public function updatedBy(): User
    {
        return $this->updated_by ? Users::user($this->updated_by) : Users::guest();
    }

    public function createdByUUID(): ?string
    {
        return $this->created_by;
    }

    public function updatedByUUID(): ?string
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
