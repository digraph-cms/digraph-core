<?php

namespace DigraphCMS\HTTP;

class ArbitraryRedirectException extends RedirectException
{
    protected $url, $permanent, $preserveMethod;

    public function __construct(string $url, bool $permanent = false, bool $preserveMethod = false)
    {
        $this->url = $url;
        $this->permanent = $permanent;
        $this->preserveMethod = $preserveMethod;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function permanent(): bool
    {
        return $this->permanent;
    }

    public function preserveMethod(): bool
    {
        return $this->preserveMethod;
    }
}
