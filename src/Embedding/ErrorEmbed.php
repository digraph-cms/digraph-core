<?php

namespace DigraphCMS\Embedding;

class ErrorEmbed extends AbstractEmbed
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function srcHash(): string
    {
        return md5(get_called_class() . $this->message);
    }

    public function aspectRatio(): float
    {
        return 0;
    }

    protected function html(): string
    {
        return $this->message;
    }

    public function classes(): array
    {
        return ['media-error'];
    }
}
