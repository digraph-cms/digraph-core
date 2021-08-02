<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractDataObject;
use DigraphCMS\URL\URL;

class Page extends AbstractDataObject
{
    const SOURCE = Pages::class;
    protected $slug, $previousSlug;

    protected function construct(array $data, array $metadata)
    {
        $this->slug(@$metadata['slug'] ?? substr($this->uuid(), 0, 8), true);
        $this->previousSlug = null;
    }

    public function class(): string
    {
        return 'page';
    }

    public function slug(string $slug = null, $unique = false): string
    {
        if ($slug != null) {
            $this->previousSlug = $this->slug;
            $this->slug = $slug;
            if ($unique) {
                // if $unique is requested slug will be renamed if it collides
                // with an existing slug or UUID
                while (!Pages::validateSlug($this->slug)) {
                    $this->slug = $slug .= '-' . bin2hex(random_bytes(4));
                }
            } else {
                // otherwise slug must still *never* collide with a UUID
                // UUIDs *must* be usable for unique/canonical URLs
                while (Pages::uuidExists($this->slug)) {
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

    public function name(): string
    {
        return $this->uuid();
    }

    public function urlName(URL $url): string
    {
        $name = $this->name();
        if ($url->action() != 'index') {
            $name .= ' ' . $url->action();
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

    public function url(string $action = '', array $args = []): URL
    {
        if ($action && !preg_match('/\.[a-z0-9]+$/', $action)) {
            $action .= '.html';
        }
        $url = new URL(
            '/' .
                ($this->slug() ?? $this->uuid()) .
                '/' .
                $action
        );
        $url->query($args);
        return $url;
    }
}
