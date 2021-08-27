<?php

namespace DigraphCMS\Users;

use DigraphCMS\Config;

class CASUserSource extends AbstractUserSource
{
    public function title(): string
    {
        return 'CAS signin';
    }

    public function providers(): array
    {
        return @array_keys(Config::get('cas.providers')) ?? [];
    }

    public function allSigninURLs(?string $bounce): array
    {
        $urls = [];
        foreach ($this->providers() as $id) {
            $url = $this->signinUrl($bounce);
            $url->arg('_provider',$id);
            $url->setName(Config::get("cas.providers.$id.name"));
            $urls[$this->name()."_$id"] = $url;
        }
        return $urls;
    }
}
