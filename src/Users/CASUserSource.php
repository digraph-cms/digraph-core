<?php

namespace DigraphCMS\Users;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;

class CASUserSource extends AbstractUserSource
{
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

    public static function authorizeUser(string $cas_provider, string $cas_id, string $user_uuid)
    {
        DB::query()->insertInto(
            'user_cas',
            [
                'cas_user' => $user_uuid,
                'cas_provider' => $cas_provider,
                'cas_id' => $cas_id
            ]
        )->execute();
    }

    public static function deauthorizeUser(string $cas_provider, string $user_uuid)
    {
        DB::query()->deleteFrom('user_cas')
            ->where('cas_user = ? AND cas_provider = ?', [$cas_provider, $user_uuid])
            ->execute();
    }

    public function active(): bool
    {
        return count($this->providers()) > 0;
    }

    public function title(): string
    {
        return 'Third-party CAS signin';
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
        $result = DB::query()->from('user_cas')
            ->where('cas_provider = ? AND cas_id = ?', [$provider, $id])
            ->execute();
        if ($result && $result = $result->fetch()) {
            return $result['cas_user'];
        } else {
            return null;
        }
    }

    public function providers(): array
    {
        return @array_keys(Config::get('cas.providers')) ?? [];
    }
}
