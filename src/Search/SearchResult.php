<?php

namespace DigraphCMS\Search;

use DigraphCMS\URL\URL;

class SearchResult
{
    protected $title, $url, $body, $query;
    protected $generatedBody;

    public function __construct(string $title, URL $url, string $body, string $query)
    {
        $this->title = $title;
        $this->url = $url;
        $this->body = $body;
        $this->query = $query;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function url(): URL
    {
        return $this->url;
    }

    public function body(): string
    {
        return $this->generatedBody
            ?? $this->generatedBody = $this->generateBody();
    }

    protected function generateBody()
    {
        return substr($this->body, 0, 250);
    }
}
