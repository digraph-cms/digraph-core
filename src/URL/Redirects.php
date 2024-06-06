<?php

namespace DigraphCMS\URL;

use DigraphCMS\Cache\Cache;
use DigraphCMS\DB\DB;
use DigraphCMS\Session\Session;

class Redirects
{
    const DISALLOWED = [
        '/~admin/url_management/redirects/',
        '/~admin/url_management/redirects/_add_redirect.html',
        '/admin/url_management/redirects/',
        '/admin/url_management/redirects/_add_redirect.html',
    ];

    public static function destination(URL|null $url): ?URL
    {
        if (is_null($url)) return null;
        // first search by full path string, so that there can be specific
        // results for particular query strings, but also general results for
        // a whole path
        return Cache::get(
            'redirect/' . md5($url),
            function () use ($url) {
                $pathString = urldecode($url->pathString());
                return static::getDestination($pathString . $url->queryString())
                    ?? static::getDestination($pathString);
            }
        );
    }

    public static function create(URL $redirect_from, URL $redirect_to): void
    {
        // skip disallowed from URLs
        if (in_array($redirect_from->pathString(), static::DISALLOWED)) return;
        // skip obvious infinite redirects
        if ($redirect_from->fullPathString() == $redirect_to->fullPathString()) return;
        // delete any existing record and re-add
        static::delete($redirect_from);
        DB::query()->insertInto(
            'redirect',
            [
                'redirect_from' => $redirect_from->fullPathString(),
                'redirect_to' => $redirect_to->fullPathString(),
                'created' => time(),
                'created_by' => Session::uuid(),
            ]
        )->execute();
    }

    public static function delete(string|URL $redirect_from): void
    {
        if ($redirect_from instanceof URL) $redirect_from = $redirect_from->fullPathString();
        DB::query()->delete('redirect')
            ->where('redirect_from', $redirect_from)
            ->execute();
    }

    protected static function getDestination(string $path): ?URL
    {
        $result = DB::query()->from('redirect')
            ->where('redirect_from', $path)
            ->fetch();
        return $result ? new URL($result['redirect_to']) : null;
    }
}
