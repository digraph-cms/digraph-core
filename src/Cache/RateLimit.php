<?php

namespace DigraphCMS\Cache;

use DigraphCMS\DB\DB;

class RateLimit
{
    public static function run(string $namespace, string $name, int $ttl, callable $callback)
    {
        DB::beginTransaction();
        if (static::check($namespace, $name)) {
            static::set($namespace, $name, $ttl);
            $output = $callback();
        } else {
            $output = null;
        }
        DB::commit();
        return $output;
    }

    public static function set(string $namespace, string $name, int $ttl): void
    {
        DB::beginTransaction();
        $query = DB::query()
            ->from('rate_limit')
            ->where('namespace', $namespace)
            ->where('name', $name);
        if ($query->count()) {
            DB::query()
                ->update('rate_limit', ['expires' => time() + $ttl])
                ->where('namespace', $namespace)
                ->where('name', $name)
                ->execute();
        } else {
            DB::query()
                ->insertInto('rate_limit', [
                    'namespace' => $namespace,
                    'name' => $name,
                    'expires' => time() + $ttl
                ])
                ->execute();
        }
        DB::commit();
    }

    public static function check(string $namespace, string $name): bool
    {
        $query = DB::query()
            ->from('rate_limit')
            ->where('namespace', $namespace)
            ->where('name', $name)
            ->where('expires < ?', time());
        return !$query->count();
    }
}
