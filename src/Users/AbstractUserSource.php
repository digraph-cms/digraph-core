<?php

namespace DigraphCMS\Users;

use DigraphCMS\DB\DB;
use DigraphCMS\URL\URL;

abstract class AbstractUserSource
{
    protected $name;

    abstract public function title(): string;
    abstract public function allSigninURLs(?string $bounce): array;
    abstract public function providers(): array;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    protected function signinUrl(?string $bounce): URL
    {
        $url = new URL('/~signin/' . $this->name() . '.html');
        if ($bounce) {
            $url->arg('bounce', $bounce);
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
                'provider_id' => $provider_id
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
        if ($result && $result = $result->fetch()) {
            return $result['user_uuid'];
        } else {
            return null;
        }
    }
}
