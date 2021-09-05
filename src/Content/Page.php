<?php

namespace DigraphCMS\Content;

use ArrayAccess;
use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Flatrr\FlatArrayTrait;

class Page implements ArrayAccess
{
    use FlatArrayTrait {
        set as protected rawSet;
        unset as protected rawUnset;
    }

    protected $uuid, $slug, $previousSlug, $name;
    protected $created, $created_by;
    protected $updated, $updated_by;
    protected $slugCollisions;

    public function body(): string
    {
        return "<h1>" . $this->title() . "</h1>";
    }

    public function __construct(array $data = [], array $metadata = [])
    {
        $this->uuid = @$metadata['uuid'] ?? Digraph::uuid();
        $this->name = @$metadata['name'] ?? 'Untitled';
        $this->created = @$metadata['created'] ?? new DateTime();
        $this->created_by = @$metadata['created_by'] ?? Session::user();
        $this->updated = @$metadata['updated'] ?? new DateTime();
        $this->updated_last = clone $this->updated;
        $this->updated_by = @$metadata['updated_by'] ?? Session::user();
        $this->rawSet(null, $data);
        $this->slug = @$metadata['slug'] ?? substr($this->uuid(), 0, 8);
        if (!Pages::validateSlug($this->slug)) {
            throw new \Exception("Slug " . $this->slug . " is not valid");
        }
        $this->changed = false;
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
            return Permissions::inGroup('admins', Users::current());
        }
        return null;
    }

    public function slugCollisions(): bool
    {
        if ($this->slugCollisions === null) {
            $this->slugCollisions =
                Router::staticRouteExists($this->slug(), 'index') ||
                Pages::countAll($this->slug()) > 1;
        }
        return $this->slugCollisions;
    }

    public function routeClasses(): array
    {
        return ['page', '_any'];
    }

    public function class(): string
    {
        return 'page';
    }

    public function slug(string $slug = null, $unique = false): string
    {
        if ($slug !== null) {
            $this->previousSlug = $this->previousSlug ?? $this->slug;
            $this->slug = $slug;
            if (!Pages::validateSlug($this->slug)) {
                throw new \Exception("Slug $slug is not valid");
            }
            if ($unique) {
                // if $unique is requested slug will be renamed if it collides
                // with an existing slug or UUID
                while (Pages::exists($this->slug) || Pages::slugExists($this->slug)) {
                    $this->slug = $slug .= '-' . bin2hex(random_bytes(4));
                }
            } else {
                // otherwise slug must still *never* collide with a UUID
                // UUIDs *must* be usable for unique/canonical URLs
                while (Pages::exists($this->slug)) {
                    $this->slug = $slug .= '-' . bin2hex(random_bytes(4));
                }
            }
        }
        return $this->slug;
    }

    public function previousSlug(): ?string
    {
        return $this->previousSlug;
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
     * @return string
     */
    public function title(URL $url = null, bool $inPageContext = false): string
    {
        $name = $this->name();
        if ($url && $url->action() != 'index') {
            if ($inPageContext) {
                return $url->action();
            } else {
                return $name . ': ' . $url->action();
            }
        }
        return $name;
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
        $url = new URL("/$slug/$action");
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

    public function uuid(): string
    {
        return $this->uuid;
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
