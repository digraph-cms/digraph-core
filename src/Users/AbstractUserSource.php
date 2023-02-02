<?php

namespace DigraphCMS\Users;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\URL\URL;

abstract class AbstractUserSource
{
    protected $name;

    abstract public function title(): string;
    abstract public function allSigninURLs(?string $bounce): array;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function providerName(string $provider): string
    {
        return Config::get("user_sources." . $this->name . ".providers.$provider.name");
    }

    public function providerActive(string $provider): bool
    {
        return !!Config::get("user_sources." . $this->name . ".providers.$provider.active");
    }

    public function providers(): array
    {
        return array_filter(
            array_keys(Config::get('user_sources.' . $this->name . '.providers') ?? []),
            function ($provider) {
                return Config::get('user_sources.' . $this->name . '.providers.' . $provider . '.active');
            }
        );
    }

    protected function signinUrl(?string $bounce): URL
    {
        $url = new URL('/~signin/_signin.html?_source=' . $this->name());
        if ($bounce) {
            $url->arg('_bounce', $bounce);
        }
        return $url;
    }

    public function authorizeUser(string $user_uuid, string $provider, string $provider_id)
    {
        DB::query()->insertInto(
            'user_source',
            [
                'user_uuid' => $user_uuid,
                'source' => $this->name,
                'provider' => $provider,
                'provider_id' => $provider_id,
                'created' => time(),
            ]
        )->execute();
    }

    public function active(): bool
    {
        return count($this->providers()) > 0;
    }

    /**
     * Look up the user UUID associated with a given provider name and provider
     * user ID.
     *
     * @param string $provider
     * @param string $id
     * @return string|null
     */
    public function lookupUser(string $provider, string $id): ?string
    {
        $result = DB::query()->from('user_source')
            ->where(
                'source = ? AND provider = ? AND provider_id = ?',
                [$this->name, $provider, $id]
            );
        if ($result = $result->fetch()) {
            return $result['user_uuid'];
        } else {
            return null;
        }
    }
}
