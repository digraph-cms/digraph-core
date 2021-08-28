<?php

namespace DigraphCMS\Users;

use DigraphCMS\Config;

class CASUserSource extends AbstractUserSource
{
    public function title(): string
    {
        return 'CAS';
    }

    public function allSigninURLs(?string $bounce): array
    {
        $urls = [];
        foreach ($this->providers() as $id) {
            $url = $this->signinUrl($bounce);
            $url->arg('_provider',$id);
            $url->setName(Config::get("user_sources.cas.providers.$id.name"));
            $urls[$this->name()."_$id"] = $url;
        }
        return $urls;
    }
}
