<?php

namespace DigraphCMS\Search;

use DigraphCMS\URL\URL;

class SearchResult
{
    protected $title, $url, $body, $query;
    protected $snippet;

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

    public function snippet(): string
    {
        return $this->snippet
            ?? $this->snippet = $this->generateSnippet();
    }

    protected function generateSnippet()
    {
        $bodyLength = strlen($this->body);
        $queryWords = preg_split('/[\s]/', $this->query);
        $locations = $this->wordLocations($queryWords, $this->body);
        $startPosition = $this->snippetStart($locations);
        if ($bodyLength - $startPosition < 250) {
            $startPosition = floor($startPosition - ($bodyLength - $startPosition) / 2);
        }
        $endPosition = $startPosition + 250;
        $snippet = [];
        $bodyWords = preg_split('/[\s]/', $this->body);
        $wordStart = 0;
        foreach ($bodyWords as $bodyWord) {
            $wordEnd = $wordStart + strlen($bodyWord);
            if ($wordEnd >= $startPosition) {
                $matches = false;
                foreach ($queryWords as $queryWord) {
                    if (stripos($bodyWord, $queryWord) !== false) {
                        $matches = true;
                        break;
                    }
                }
                if (strlen($bodyWord) > 25) $bodyWord = substr($bodyWord, 0, 25);
                $snippet[] = $matches
                    ? "<strong>$bodyWord</strong>"
                    : $bodyWord;
            }
            $wordStart = $wordEnd + 1;
            if ($wordStart > $endPosition) break;
        }
        return implode(' ', $snippet);
    }

    protected function snippetStart(array $locations)
    {
        if (!$locations) return 0;
        $start = $locations[0];
        $count = count($locations);
        $smallestDiff = INF;
        if (count($locations) > 2) {
            for ($i = 1; $i < $count; $i++) {
                if ($i == $count - 1) $diff = $locations[$i] - $locations[$i - 1];
                else $diff = $locations[$i + 1] - $locations[$i];
                if ($smallestDiff > $diff) {
                    $smallestDiff = $diff;
                    $start = $locations[$i];
                }
            }
        }
        if ($start > 50) return $start - 50;
        else return 0;
    }

    protected function wordLocations(array $words, string $body)
    {
        $locations = [];
        $bodyLength = strlen($body);
        foreach ($words as $word) {
            $wordLength = strlen($word);
            $location = stripos($body, $word);
            while ($location !== false) {
                $locations[] = $location;
                $location = stripos($body, $word, $location + $wordLength);
            }
        }
        $locations = array_unique($locations);
        sort($locations);
        return $locations;
    }
}
