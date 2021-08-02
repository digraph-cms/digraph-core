<?php

namespace DigraphCMS\HTTP;

abstract class AbstractHeaders
{
    const AUTO_INGEST = [];
    protected $headers = [];

    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $value) {
            $key = strtolower($key);
            if (in_array($key, static::AUTO_INGEST)) {
                $this->set($key, $value);
            }
        }
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    public function set(string $key, $value): string
    {
        $key = strtolower($key);
        $fn = 'set_' . str_replace('-', '_', $key);
        if (method_exists($this, $fn)) {
            $value = $this->$fn($value);
        }
        return $this->headers[$key] = $value;
    }

    public function get(string $key): ?string
    {
        $key = strtolower($key);
        $fn = 'get_' . str_replace('-', '_', $key);
        if (method_exists($this, $fn)) {
            return $this->$fn(@$this->headers[$key]);
        }
        return @$this->headers[$key];
    }
}
