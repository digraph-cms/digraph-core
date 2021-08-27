<?php

namespace DigraphCMS\Users;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use League\OAuth2\Client\Provider\AbstractProvider;

class OAuth2UserSource extends AbstractUserSource
{
    protected $providers = [];

    public function title(): string
    {
        return 'Third-party OAuth signin';
    }

    public function allSigninURLs(?string $bounce): array
    {
        $urls = [];
        foreach ($this->providers() as $id) {
            $url = $this->signinUrl($bounce);
            $url->arg('_provider',$id);
            $url->setName(Config::get("oauth2.providers.$id.name"));
            $urls[$this->name()."_$id"] = $url;
        }
        return $urls;
    }

    public function providers(): array
    {
        return array_filter(
            array_keys(Config::get('oauth2.providers')),
            function ($name) {
                return Config::get("oauth2.providers.$name.id") && Config::get("oauth2.providers.$name.secret");
            }
        );
    }

    public function provider(string $name, string $bounce = null): ?AbstractProvider
    {
        if (!isset($this->providers[$name])) {
            if (Config::get("oauth2.providers.$name.id") && Config::get("oauth2.providers.$name.secret")) {
                $provider = Config::get("oauth2.providers.$name");
                $class = $provider['class'];
                $config = @$provider['config'] ?? [];
                $config['clientId'] = $provider['id'];
                $config['clientSecret'] = $provider['secret'];
                $config['redirectUri'] = $this->redirectUrl($name, $bounce);
                $this->providers[$name] = new $class($config);
            } else {
                $this->providers[$name] = null;
            }
        }
        return $this->providers[$name];
    }

    public static function redirectUrl($name, string $bounce = null)
    {
        $url = new URL('/~signin/oauth2.html?_provider=' . $name);
        if ($bounce) {
            $url->arg('bounce', $bounce);
        }
        $url = $url->__toString();
        $url = preg_replace('@^//@', URLs::siteProtocol() . '://', $url);
        return $url;
    }
}
