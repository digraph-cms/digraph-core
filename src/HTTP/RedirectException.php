<?php

namespace DigraphCMS\HTTP;

use DigraphCMS\URL\URL;
use Exception;

class RedirectException extends Exception
{
    protected $url, $permanent, $preserveMethod;
    protected string|null $targetFrame;

    public function __construct(URL $url, bool $permanent = false, bool $preserveMethod = false, string|null $targetFrame = null)
    {
        $this->url = clone $url;
        $this->permanent = $permanent;
        $this->preserveMethod = $preserveMethod;
        $this->targetFrame = $targetFrame;
    }

    public function url(): string
    {
        return $this->url->__toString();
    }

    public function permanent(): bool
    {
        return $this->permanent;
    }

    public function preserveMethod(): bool
    {
        return $this->preserveMethod;
    }

    public function targetFrame(): string|null
    {
        return $this->targetFrame;
    }
}
