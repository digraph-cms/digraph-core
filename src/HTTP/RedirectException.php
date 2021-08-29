<?php

namespace DigraphCMS\HTTP;

use DigraphCMS\URL\URL;
use Exception;

class RedirectException extends Exception
{
    protected $url, $permanent, $preserveMethod;

    public function __construct(URL $url, bool $permanent = false, bool $preserveMethod = false)
    {
        $this->url = clone $url;
        $this->permanent = $permanent;
        $this->preserveMethod = $preserveMethod;
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
}
