<?php

namespace DigraphCMS\URL;

use DateTime;
use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use Throwable;

class WaybackMachine
{
    protected static $checksCount = 0;

    public static function url(string $url, DateTime $date = null): ?URL
    {
        if (static::$checksCount >= Config::get('wayback.max_checks')) {
            return null;
        }
        $apiResult = static::api($url, $date);
        unset($apiResult['timestamp']);
        return Cache::get(
            'wayback/url/' . md5(serialize($apiResult)),
            function () use ($apiResult, $url, $date) {
                if ($apiResult) {
                    // ID is a deterministic UUID based on actual API result
                    $uuid = Digraph::uuid(serialize([$url, $date]));
                    // save data into table if it doesn't already exist in there
                    $query = DB::query()->from('wayback')
                        ->where('uuid = ?', [$uuid]);
                    if (!$query->count()) {
                        DB::query()->insertInto(
                            'wayback',
                            [
                                'uuid' => $uuid,
                                'url' => $url,
                                'date' => $date ? $date->getTimestamp() : null,
                                'data' => json_encode($apiResult),
                                'created' => time()
                            ]
                        )->execute();
                    }
                    // return URL
                    return new URL("/~wayback/$uuid.html");
                } else {
                    return null;
                }
            },
            Config::get('wayback.ttl')
        );
    }
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
        if (static::$checksCount >= Config::get('wayback.max_checks')) {
            return true;
        }
        // strip fragment portion of URL
        $url = preg_replace('/#.*$/', '', $url);
        // cache output
        return Cache::get(
            'wayback/check/' . md5($url),
            function () use ($url) {
                try {
                    static::$checksCount++;
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, Config::get('wayback.connect_timeout'));
                    curl_setopt($ch, CURLOPT_TIMEOUT, Config::get('wayback.connect_timeout_connect'));
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
            Config::get('wayback.ttl')
        );
    }

    /**
     * Make a Wayback Machine API request to find a copy of the given URL.
     * Optionally returns a copy nearest to the given datetime. Results are
     * cached according to config wayback.ttl
     * 
     * NOTE: May return null without actually querying the API if the
     * maximum number of checks has been reached.
     *
     * @param string $url
     * @param DateTime|null $date
     * @return array|null
     */
    public static function api(string $url, DateTime $date = null): ?array
    {
        if (static::$checksCount >= Config::get('wayback.max_checks')) {
            return null;
        }
        return Cache::get(
            'wayback/api/' . md5(serialize([$url, $date])),
            function () use ($url, $date) {
                try {
                    static::$checksCount++;
                    $wb = sprintf(
                        'http://archive.org/wayback/available?url=%s',
                        urlencode($url)
                    );
                    if ($date) {
                        $wb .= '&timestamp=' . $date->format('YmdHis');
                    }
                    $ch = curl_init($wb);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                    curl_close($ch);
                    if ($code == 200) {
                        $json = json_decode($response, true);
                        if ($json && $json['archived_snapshots']) {
                            return $json;
                        } else {
                            return null;
                        }
                    } else {
                        return null;
                    }
                } catch (Throwable $th) {
                    return null;
                }
            },
            Config::get('wayback.ttl')
        );
    }
}
