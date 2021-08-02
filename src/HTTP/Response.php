<?php

namespace DigraphCMS\HTTP;

use DigraphCMS\Config;
use DigraphCMS\Content\Page;
use DigraphCMS\URL\URL;

class Response
{
    protected $status = 200;
    protected $headers = [];
    protected $content = '';
    protected $url = null;
    protected $page = null;
    protected $browserTTL = null;
    protected $cacheTTL = null;
    protected $private = null;

    public function __construct(URL $url, int $status = null)
    {
        $this->url = clone $url;
        $this->status($status);
        $this->headers = new ResponseHeaders();
        $this->headers->response($this);
    }

    public function redirect(string $url, bool $permanent = false)
    {
        $this->headers()->set('Location', $url);
        $this->status($permanent ? 308 : 307);
    }

    public function private(bool $private = null): bool
    {
        if ($private !== null) {
            $this->headers()->private($private);
            $this->private = $private;
        }
        return $this->private;
    }

    public function cacheTTL(int $ttl = null): int
    {
        if ($ttl !== null) {
            $this->cacheTTL = $ttl;
        }
        return
            // explicitly set value
            $this->cacheTTL ??
            // config value for any page class
            Config::get('page.cachettl._any') ??
            // config value for this page class
            ($this->page() ? Config::get('page.cachettl.' . $this->page()->class()) : null) ??
            // page object's ttl
            ($this->page() ? $this->page->cacheTTL($this->url()->action()) : null) ??
            // default of 60
            60;
    }

    public function browserTTL(int $ttl = null): int
    {
        if ($ttl !== null) {
            $this->browserTTL = $ttl;
        }
        return
            // explicitly set value
            $this->browserTTL ??
            // config value for any page class
            Config::get('page.browserttl._any') ??
            // config value for this page class
            ($this->page() ? Config::get('page.browserttl.' . $this->page()->class()) : null) ??
            // page object's ttl
            ($this->page() ? $this->page->browserTTL($this->url()->action()) : null) ??
            // default of 60
            60;
    }

    public function page(Page $page = null): ?Page
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

    public function url(): URL
    {
        return clone $this->url;
    }

    public function status(int $status = null): int
    {
        if ($status !== null) {
            $this->status = $status;
        }
        return $this->status;
    }

    public function content(string $content = null): string
    {
        if ($content !== null) {
            $this->content = $content;
        }
        return $this->content;
    }

    public function render()
    {
        $this->renderHeaders();
        $this->renderContent();
    }

    public function renderHeaders()
    {
        foreach ($this->headers()->toArray() as $key => $value) {
            header("$key: $value");
        }
        http_response_code($this->status());
    }

    public function renderContent()
    {
        echo $this->content();
    }
}
