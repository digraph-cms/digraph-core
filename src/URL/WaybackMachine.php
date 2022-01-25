<?php

namespace DigraphCMS\URL;

use DateTime;
use DateTimeZone;
use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use Throwable;

class WaybackMachine
{
    protected static $checksCount = 0;

    /**
     * Check whether a given URL appears to be broken. Does so by making an
     * HTTP request to it and returning true/false depending on whether the
     * response indicates an error. Cached according to config wayback.ttl
     * 
     * NOTE: May return true without checking if number of checks per pageview
     * is surpassed. May also return and cache an incorrect true value if
     * a timeout occurs.
     *
     * @param string $url
     * @return boolean|null
     */
    public static function check(string $url): ?bool
    {
        static $cache = [];
        // strip fragment portion of URL
        $url = preg_replace('/#.*$/', '', $url);

        // return from memory cache if available
        if (isset($cache[$url])) {
            return $cache[$url];
        }

        // return true without checking if we've reached the per-request max of checks
        if (static::$checksCount >= Config::get('wayback.max_checks')) {
            return $cache[$url] = true;
        }

        // cache output
        return $cache[$url] = Cache::get(
            'wayback/check/' . md5($url),
            function () use ($url) {
                try {
                    static::$checksCount++; // increment check counter if we're actually running the check
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, Config::get('wayback.check_timeout'));
                    curl_setopt($ch, CURLOPT_TIMEOUT, Config::get('wayback.check_timeout_connect'));
                    curl_exec($ch);
                    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                    $errno = curl_errno($ch);
                    curl_close($ch);
                    $success = $code >= 200 && $code < 400;
                    if ($success) {
                        return true;
                    } elseif ($errno == 28) {
                        return true;
                    } else {
                        return false;
                    }
                } catch (Throwable $th) {
                    return null;
                }
            },
            Config::get('wayback.check_ttl')
        );
    }

    protected static function normalizeURL(string $url): string
    {
        $url = parse_url($url);
        $normal = $url['host'];
        if (@$url['port']) {
            $normal .= ':' . $url['port'];
        }
        $normal .= @$url['path'] ?? '/';
        if (@$url['query']) {
            $normal .= '?' . $url['query'];
        }
        return $normal;
    }

    public static function getByUUID(string $uuid): ?WaybackResult
    {
        static $cache = [];

        // return from memory cache if available
        if (isset($cache[$uuid])) {
            return $cache[$uuid];
        }

        // try to retrieve from database
        $query = DB::query()->from('wayback_machine')
            ->where('uuid = ?', [$uuid])
            ->limit(1);
        if ($row = $query->fetch()) {
            // return/cache null if wb_time is null, this means no result was found
            if (!$row['wb_time']) {
                return $cache[$uuid] = null;
            }
            // otherwise return/cache a result object
            return $cache[$uuid] = new WaybackResult(
                $row['url'],
                $row['wb_url'],
                $row['wb_time'],
                $row['created']
            );
        }
        return $cache[$uuid] = null;
    }

    public static function get(string $url): ?WaybackResult
    {
        static $cache = [];
        $url = static::normalizeURL($url);

        // return from memory cache if available
        if (isset($cache[$url])) {
            return $cache[$url];
        }

        // try to retrieve from database
        $query = DB::query()->from('wayback_machine')
            ->where('url = ?', [$url])
            ->order('wb_time desc')
            ->limit(1);
        if ($row = $query->fetch()) {
            // return/cache null if wb_time is null, this means no result was found
            if (!$row['wb_time']) {
                return $cache[$url] = null;
            }
            // otherwise return/cache a result object
            return $cache[$url] = new WaybackResult(
                $row['url'],
                $row['wb_url'],
                $row['wb_time']
            );
        }

        // we might have to actually hit the API now

        // return/cache null if max checks per request is exceeded
        if (static::$checksCount >= Config::get('wayback.max_api_calls')) {
            return null;
        }
        static::$checksCount++;

        // retrieve from API if not found in DB
        $wb = sprintf(
            'http://archive.org/wayback/available?url=%s',
            urlencode($url)
        );
        $ch = curl_init($wb);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code == 200) {
            $json = json_decode($response, true);
            if ($json['archived_snapshots']) {
                $return = $result = new WaybackResult(
                    $url,
                    $json['archived_snapshots']['closest']['url'],
                    DateTime::createFromFormat(
                        'YmdHis',
                        $json['archived_snapshots']['closest']['timestamp'],
                        new DateTimeZone('UTC')
                    )->getTimestamp()
                );
            } else {
                $return = null;
                $result = new WaybackResult(
                    $url,
                    null,
                    null
                );
            }
            // insert result into database
            $check = DB::query()->from('wayback_machine')
                ->where('uuid = ?', [$result->uuid()])
                ->limit(1);
            if (!$check->count()) {
                DB::query()->insertInto(
                    'wayback_machine',
                    [
                        'uuid' => $result->uuid(),
                        'url' => $result->originalURL(),
                        'wb_time' => $result->wbTime()->getTimestamp(),
                        'wb_url' => $result->wbURL(),
                        'created' => $result->created()->getTimestamp(),
                    ]
                )->execute();
            }
            // return return value
            return $cache[$url] = $return;
        } else {
            return $cache[$url] = null;
        }
    }
}
