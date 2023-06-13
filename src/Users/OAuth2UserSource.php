<?php

namespace DigraphCMS\Users;

use DigraphCMS\Config;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use League\OAuth2\Client\Provider\AbstractProvider;

class OAuth2UserSource extends AbstractUserSource
{
    protected $providers = [];

    public function title(): string
    {
        return 'OAuth 2.0';
    }

    public function allSigninURLs(?string $bounce): array
    {
        $urls = [];
        foreach ($this->providers() as $id) {
            $url = $this->signinUrl($bounce);
            $url->arg('_provider', $id);
            $url->setName(Config::get("user_sources.oauth2.providers.$id.name"));
            $urls[$this->name() . "_$id"] = $url;
        }
        return $urls;
    }

    public function provider(string $name, string $bounce = null): ?AbstractProvider
    {
        if (!isset($this->providers[$name])) {
            if (Config::get("user_sources.oauth2.providers.$name.id") && Config::get("user_sources.oauth2.providers.$name.secret")) {
                $provider = Config::get("user_sources.oauth2.providers.$name");
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

    public function redirectUrl($provider, string $bounce = null)
    {
        $url = new URL('/signin/_signin.html');
        $url->arg('_provider', $provider);
        $url->arg('_source', $this->name());
        if ($bounce) {
            $url->arg('_bounce', $bounce);
        }
        $url->normalize();
        $url = $url->__toString();
        $url = preg_replace('@^//@', URLs::siteProtocol() . '://', $url);
        return $url;
    }
}
