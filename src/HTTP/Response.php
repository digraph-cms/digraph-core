<?php

namespace DigraphCMS\HTTP;

use DigraphCMS\URL;

class Response
{
    protected $status = 200;
    protected $headers = [];
    protected $content = null;
    protected $url = null;

    public function __construct(URL $url, int $status = null)
    {
        $this->url = clone $url;
        $this->status($status);
        $this->headers = new ResponseHeaders();
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
        return $this->status;
    }

    public function render()
    {
        $this->renderHeaders();
        $this->renderContent();
    }

    public function renderHeaders()
    {
        // TODO: render headers
    }

    public function renderContent()
    {
        // TODO: render content
        var_dump($this);
    }
}
