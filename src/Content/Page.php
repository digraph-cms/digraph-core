<?php

namespace DigraphCMS\Content;

use ArrayAccess;
use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Forms\Fields;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
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

    public function fields($action = null): array
    {
        $fields = [];
        $fields['page_name'] = [
            'weight' => 100,
            'field' => $field = Fields::name($this),
            'sets' => function () use ($field) {
                $this->name($field);
            }
        ];
        return $fields;
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
        $this->slug(@$metadata['slug'] ?? substr($this->uuid(), 0, 8), false);
        $this->previousSlug = null;
        Dispatcher::dispatchEvent('onPageConstruct', [$this]);
        $this->changed = false;
    }

    public function slugCollisions(): bool
    {
        if ($this->slugCollisions === null) {
            $this->slugCollisions = Pages::getAll($this->slug())->count() > 1;
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
            $this->previousSlug = $this->slug;
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

    public function name(string $name = null): string
    {
        if ($name) {
            $this->name = $name;
        }
        return $this->name;
    }

    public function title(URL $url = null): string
    {
        $name = $this->name();
        if ($url && $url->action() != 'index') {
            $name .= ': ' . $url->action();
        }
        return $name;
    }

    public function cacheTTL(string $action): ?int
    {
        return null;
    }

    public function browserTTL(string $action): ?int
    {
        return null;
    }

    public function url(string $action = '', array $args = [], bool $uuid = false): URL
    {
        if ($action && !preg_match('/\.[a-z0-9]+$/', $action)) {
            $action .= '.html';
        }
        $slug = ($uuid || $this->slugCollisions()) ? $this->uuid() : $this->slug();
        $url = new URL("/$slug/$action");
        $url->query($args);
        return $url;
    }

    public function insert()
    {
        return Pages::insert($this);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function createdBy(): User
    {
        return Users::user($this->created_by);
    }

    public function updatedBy(): User
    {
        return Users::user($this->updated_by);
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
        return clone $this->updated;
    }
}
