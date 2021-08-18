<?php

namespace DigraphCMS\Users;

use DigraphCMS\URL\URL;

abstract class AbstractUserSource
{
    protected $name;

    abstract public function title(): string;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function signinUrl(?string $bounce): URL
    {
        $url = new URL('/~signin/' . $this->name() . '.html');
        if ($bounce) {
            $url->arg('bounce', $bounce);
        }
        return $url;
    }
}
