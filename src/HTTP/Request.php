<?php

namespace DigraphCMS\HTTP;

use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

class Request
{
    protected $url = null;
    protected $originalUrl = null;
    protected $method = null;
    protected $headers = null;
    protected $post = [];
    protected $user = null;
    protected $hash = null;

    public function __construct(URL $url, string $method, RequestHeaders $headers, array $post)
    {
        $this->originalUrl = clone $url;
        $this->url($url);
        $this->method = $method;
        $this->headers = $headers;
        $this->post = $post;
        $this->user = Users::current();
    }

    public function hash()
    {
        if (!$this->hash) {
            $this->hash = md5(serialize([
                $this->url,
                $this->method,
                $this->headers,
                $this->post,
                $this->user,
                Cookies::cacheMutators()
            ]));
        }
        return $this->hash;
    }

    public function post(): array
    {
        return $this->post;
    }

    public function headers(): RequestHeaders
    {
        return $this->headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function url(URL $url = null): URL
    {
        if ($url) {
            $this->url = clone $url;
            $this->url->normalize();
        }
        return $this->url;
    }

    public function originalUrl(): URL
    {
        return clone $this->originalUrl;
    }
}
