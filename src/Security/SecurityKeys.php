<?php

namespace DigraphCMS\Security;

use DigraphCMS\Cache\Cache;
use DigraphCMS\DB\DB;

class SecurityKeys
{
    const REGENERATE_INTERVAL = 86400 * 7; // rotate keys weekly
    const EXPIRATION_INTERVAL = 86400 * 60; // keys fully expire after 60 days

    public static function runMaintenance(): void
    {
        // if the current key is older than REGENERATE_INTERVAL, generate a new one
        $current = static::current();
        if (!$current || $current['created'] < time() - self::REGENERATE_INTERVAL) {
            // generate a new key
            $key = self::generateString();
            DB::query()
                ->insertInto('security_key', [
                    'key' => $key,
                    'created' => time(),
                    'expires' => time() + self::EXPIRATION_INTERVAL,
                    'revoked' => null
                ])
                ->execute();
            static::clearCache();
        }
    }

    protected static function clearCache(): void
    {
        Cache::invalidate('security_keys/current');
        Cache::invalidate('security_keys/active');
    }

    public static function revoke(string $key): void
    {
        $count = DB::query()
            ->update('security_key', [
                'revoked' => time()
            ])
            ->where('key', $key)
            ->execute();
        if ($count) {
            static::clearCache();
        }
    }

    public static function generateString(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Retrieve the key portion only of the current security key, which is the most recent key that
     * should be used for signing things.
     */
    public static function currentKey(): string
    {
        return static::current()['key'];
    }

    /**
     * Retrieve the expiration time of the current security key, which is the most recent key that
     * should be used for signing things.
     */
    public static function currentExpiration(): int
    {
        return static::current()['expires'];
    }

    /**
     * Retrieve the creation time of the current security key, which is the most recent key that
     * should be used for signing things.
     */
    public static function currentCreated(): int
    {
        return static::current()['created'];
    }

    /**
     * Retrieve the entire data of the current security key, which is the most
     * recent key that should be used for signing things.
     *
     * @return array{'key': string, 'created': int, 'expires': int, 'revoked': int|null}
     */
    public static function current(): array|null
    {
        return Cache::get(
            'security_keys/current',
            function (): array|null {
                return DB::query()
                    ->from('security_key')
                    ->where('expires > ?', time())
                    ->where('revoked is null')
                    ->order('created DESC')
                    ->order('expires DESC')
                    ->fetch();
            },
            300
        );
    }

    /**
     * Return an array of all currently active keys, which are valid for
     * authenticating data at this time.
     * @return string[]
     */
    public static function active(): array
    {
        return Cache::get(
            'security_keys/active',
            function (): array {
                $query = DB::query()
                    ->from('security_key')
                    ->where('expires > ?', time())
                    ->where('revoked is null')
                    ->order('created DESC')
                    ->order('expires DESC');
                $keys = [];
                foreach ($query as $row) {
                    $keys[] = $row['key'];
                }
                return $keys;
            },
            300
        );
    }
}
