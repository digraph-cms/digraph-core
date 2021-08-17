<?php

namespace DigraphCMS\Users;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use League\OAuth2\Client\Provider\AbstractProvider;

class OAuth2UserSource extends AbstractUserSource
{
    protected $providers = [];

    public static function authorizeUser(string $oauth_provider, string $oauth_id, string $user_uuid)
    {
        DB::query()->insertInto(
            'oauth2',
            [
                'oauth_user' => $user_uuid,
                'oauth_provider' => $oauth_provider,
                'oauth_id' => $oauth_id
            ]
        )->execute();
    }

    public static function deauthorizeUser(string $oauth_provider, string $user_uuid)
    {
        DB::query()->deleteFrom('oauth2')
            ->where('oauth_user = ? AND oauth_provider = ?', [$oauth_provider, $user_uuid])
            ->execute();
    }

    public function title(): string
    {
        return 'Third-party OAuth signin';
    }

    /**
     * Look up the user UUID associated with a given provider name and provider
     * user ID.
     *
     * @param string $provider
     * @param string $id
     * @return string|null
     */
    public static function lookupUser(string $provider, string $id): ?string
    {
        $result = DB::query()->from('oauth2')
            ->where('oauth_provider = ? AND oauth_id = ?', [$provider, $id])
            ->execute();
        if ($result && $result = $result->fetch()) {
            return $result['oauth_user'];
        } else {
            return null;
        }
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

    public function provider(string $name): ?AbstractProvider
    {
        if (!isset($this->providers[$name])) {
            if (Config::get("oauth2.providers.$name.id") && Config::get("oauth2.providers.$name.secret")) {
                $provider = Config::get("oauth2.providers.$name");
                $class = $provider['class'];
                $this->providers[$name] = new $class([
                    'clientId' => $provider['id'],
                    'clientSecret' => $provider['secret']
                ]);
            } else {
                $this->providers[$name] = null;
            }
        }
        return $this->providers[$name];
    }
}
