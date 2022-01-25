<?php

namespace DigraphCMS\URL;

use DateTime;
use DigraphCMS\Context;

class WaybackResult
{

    protected $originalURL, $wbURL, $wbTime;

    public function __construct(string $originalURL, ?string $wbURL, ?int $wbTime, int $created = null)
    {
        $this->originalURL = $originalURL;
        $this->wbURL = $wbURL;
        $this->wbTime = $wbTime;
        $this->created = $created ?? time();
    }

    public function helperURL(): URL
    {
        $url = new URL('/~wayback/' . $this->uuid() . '.html');
        $url->arg('context', Context::url());
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

    public function created(): DateTime
    {
        return DateTime::createFromFormat('U', $this->created);
    }

    public function uuid(): string
    {
        return md5(serialize([
            $this->originalURL,
            $this->wbTime
        ]));
    }
}
