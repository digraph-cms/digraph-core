<?php

namespace DigraphCMS\HTTP;

use DigraphCMS\Config;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Exception as DigraphCMSException;
use DigraphCMS\Session\Session;
use Exception;

class Response
{
    protected $status = 200;
    protected ResponseHeaders $headers;
    protected $content = null;
    protected $content_file = null;
    protected $page = null;
    protected $browserTTL = null;
    protected $cacheTTL = null;
    protected $private = null;
    protected $template = null;
    protected $filename = null;
    protected $mime = null;
    protected $staleTTL;
    protected $searchIndex = false;

    public function __construct(int $status = null)
    {
        $this->status($status);
        $this->headers = new ResponseHeaders;
    }

    public function searchIndex(): bool
    {
        return $this->searchIndex;
    }

    /**
     * Set whether this response should have its content added to the search index
     *
     * @param boolean $indexResponse
     * @param boolean $allowQueries
     * @return static
     */
    public function setSearchIndex(bool $indexResponse, bool $allowQueries = false)
    {
        if (Context::request()->post()) $indexResponse = false;
        if (!$allowQueries) {
            if (Context::request()->url()->query()) $indexResponse = false;
        }
        $this->searchIndex = $indexResponse;
        return $this;
    }

    public function mime(string $mime = null): ?string
    {
        if ($mime !== null) {
            $this->mime = $mime;
        }
        return $this->mime;
    }

    public function filename(string $filename = null): ?string
    {
        if ($filename !== null) {
            $this->filename = $filename;
        }
        return $this->filename;
    }

    public function template(string $template = null): string
    {
        if ($template !== null) {
            $this->template = $template;
        }
        return $this->template
            ?? Config::get("templates.default." . $this->status())
            ?? ($this->status() == 200 ? Config::get("templates.default.default") : Config::get("templates.default.error"));
    }

    public function resetTemplate()
    {
        $this->template = null;
    }

    public function redirect(string $url, bool $permanent = false, bool $preserveMethod = false, string|null $targetFrame = null)
    {
        $this->headers()->set('Location', $url);
        if ($preserveMethod) {
            $this->status($permanent ? 308 : 307);
        } else {
            $this->status($permanent ? 301 : 302);
        }
        if ($targetFrame) {
            $this->headers()->set('X-Target-Frame', $targetFrame);
        }
    }

    public function private(bool $private = null): bool
    {
        if ($private !== null && !$this->private) {
            $this->private = $private;
        }
        return $this->private ?? Session::uuid() != 'guest';
    }

    public function enableCache()
    {
        if (!$this->cacheTTL()) $this->cacheTTL(Config::get('cache.content_ttl'));
        return $this;
    }

    public function cacheTTL(int $ttl = null): int
    {
        if ($ttl !== null) {
            $this->cacheTTL = $ttl;
        }
        return
            // explicitly set value
            $this->cacheTTL ??
            // default of 0
            0;
    }

    public function staleTTL(int $ttl = null): int
    {
        if ($ttl !== null) {
            $this->staleTTL = $ttl;
        }
        return
            // explicitly set value
            $this->staleTTL ??
            // default to 10x cacheTTL
            10 * $this->cacheTTL();
    }

    public function browserTTL(int $ttl = null): int
    {
        if ($ttl !== null) {
            $this->browserTTL = $ttl;
        }
        return
            // explicitly set value
            $this->browserTTL ??
            // page object's ttl
            ($this->page() ? $this->page->browserTTL($this->page()->url()->action()) : null) ??
            // default of 0
            0;
    }

    public function page(AbstractPage $page = null): ?AbstractPage
    {
        if ($page) {
            $this->page = $page;
        }
        return $this->page;
    }

    public function headers(): ResponseHeaders
    {
        return $this->headers;
    }

    public function status(int $status = null): int
    {
        if ($status !== null) {
            $this->status = $status;
        }
        return $this->status;
    }

    public function setContentFile(?string $file): static
    {
        var_dump($file);
        if ($file) {
            $this->filename(basename($file));
            $this->content_file = $file;
            $this->content = null;
        } else {
            $this->content_file = null;
        }
        return $this;
    }

    public function contentFile(): ?string
    {
        return $this->content_file;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        $this->content_file = null;
        return $this;
    }

    public function content(string $content = null): string
    {
        if ($content !== null) $this->setContent($content);
        if ($this->content_file) return file_get_contents($this->content_file);
        else return $this->content ?? '';
    }

    public function renderHeaders()
    {
        // render normal header
        foreach ($this->headers()->toArray() as $key => $value) {
            header("$key: $value");
        }
        header('Cache-Control: ' . $this->cacheControlHeader());
        if ($this->private()) {
            header('Pragma: no-cache');
        } else {
            header('Pragma: public');
        }
        http_response_code($this->status());
        // check if we need a canonical link
        // if response URL doesn't match original URL, set canonical header
        if ($this->status == 200) {
            $canonical = null;
            if (Context::url() != Context::request()->originalUrl()) {
                // check if original request URL matches context URL
                $canonical = Context::url();
            } elseif (Context::page()) {
                // check if canonical URL from page matches context URL
                $pageUrl = Context::page()->url(
                    Context::request()->originalUrl()->action(),
                    Context::request()->originalUrl()->query()
                );
                if (Context::request()->originalUrl() != $pageUrl) {
                    $canonical = $pageUrl;
                }
            }
            // check if we made a canonical url
            if ($canonical) {
                header('Link: <' . $canonical . '>; rel="canonical"');
            }
        }
    }

    protected function cacheControlHeader(): string
    {
        $staleTTL = $this->staleTTL();
        $output = [
            $this->private() ? 'private' : 'public',
            'max-age=' . $this->browserTTL(),
            'max-stale=' . $staleTTL,
            'stale-if-error=' . $staleTTL,
        ];
        return implode(', ', array_filter($output));
    }

    public function renderContent()
    {
        Dispatcher::dispatchEvent('onResponseRender', [$this]);
        if (Digraph::inferMime($this) == 'text/html') {
            Dispatcher::dispatchEvent('onResponseRender_html', [$this]);
        }
        if ($this->content_file) {
            if (!file_exists($this->content_file)) throw new DigraphCMSException("Response content file not found", ['file' => $this->content_file, 'response' => $this]);
            readfile($this->content_file);
        } else {
            echo $this->content();
        }
    }
}
