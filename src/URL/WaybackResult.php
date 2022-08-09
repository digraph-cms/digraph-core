<?php

namespace DigraphCMS\URL;

use DateTime;
use DigraphCMS\Context;

class WaybackResult
{

    protected $originalURL, $wbURL, $wbTime;

    public function __construct(string $originalURL, ?string $wbURL, ?int $wbTime)
    {
        $this->originalURL = $originalURL;
        $this->wbURL = $wbURL;
        $this->wbTime = $wbTime;
    }

    public function helperURL(URL $context = null): URL
    {
        $url = new URL('/~wayback/' . md5($this->originalURL) . '.html');
        $url->arg('context', $context ?? Context::url());
        return $url;
    }

    public function originalURL(): string
    {
        return $this->originalURL;
    }

    public function wbURL(): ?string
    {
        return $this->wbURL;
    }

    public function wbTime(): ?DateTime
    {
        if (!$this->wbTime) {
            return null;
        }
        return DateTime::createFromFormat('U', $this->wbTime);
    }
}
