<?php

namespace DigraphCMS\HTTP;

use DigraphCMS\URL\URL;

class Request
{
    protected $url = null;
    protected $originalUrl = null;
    protected $method = null;
    protected $headers = null;

    public function __construct(URL $url, string $method, RequestHeaders $headers)
    {
        $this->originalUrl = clone $url;
        $this->url($url);
        $this->method = $method;
        $this->headers = $headers;
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
