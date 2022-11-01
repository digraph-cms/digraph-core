<?php

namespace DigraphCMS\URL;

use DateTime;
use DigraphCMS\Context;
use DigraphCMS\Datastore\Datastore;

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
        $context = $context ?? Context::url();
        $hash = md5($this->originalURL);
        $hash = md5($hash . $context->pathString());
        if (!Datastore::exists('wayback', 'page', $hash)) {
            Datastore::set('wayback', 'page', $hash, null, [
                'original_url' => $this->originalURL(),
                'wb_url' => $this->wbURL(),
                'wb_time' => $this->wbTime()->getTimestamp(),
                'context' => $context->pathString()
            ]);
        }
        return new URL('/wayback/page:' . $hash);
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
