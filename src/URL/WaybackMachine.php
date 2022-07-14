<?php

namespace DigraphCMS\URL;

use DateTime;
use DateTimeZone;
use DigraphCMS\Cache\Cache;
use DigraphCMS\Cache\Locking;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\UI\Templates;
use Throwable;

class WaybackMachine
{
    protected static $checksCount = 0;
    protected static $active = null;

    public static function activate()
    {
        if (static::active() === false) static::$active = true;
    }

    public static function deactivate()
    {
        if (static::active() === true) static::$active = false;
    }

    public static function active(): bool
    {
        return static::$active ?? Config::get('wayback.active');
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
        if (!static::active()) return true;
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
                    $errmsg = curl_error($ch);
                    curl_close($ch);
                    $success = $code >= 200 && $code < 400;
                    if ($success) {
                        return true;
                    } elseif ($errno == 28) {
                        return true;
                    } else {
                        static::sendNotificationEmail($url, $code, $errno, $errmsg);
                        return false;
                    }
                } catch (Throwable $th) {
                    return null;
                }
            },
            Config::get('wayback.check_ttl')
        );
    }

    protected static function sendNotificationEmail($url, $code, $errno, $error)
    {
        $lock = Locking::lock(
            'wayback_notification_' . md5(Context::url() . $url),
            true,
            Config::get('wayback.notify_frequency')
        );
        if (!$lock) return;
        foreach (Config::get('wayback.notify_emails') as $addr) {
            $email = Email::newForEmail(
                'wayback',
                $addr,
                'Broken link on ' . Context::url(),
                new RichContent(
                    Templates::render(
                        'email/wayback/broken-link.php',
                        [
                            'broken_url' => $url,
                            'http_status' => $code,
                            'curl_errno' => $errno,
                            'curl_error' => $error
                        ]
                    )
                )
            );
            Emails::send($email);
        }
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
        $normal = preg_replace('/\/$/', '', $normal);
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

        if (!static::active()) return null;
        $url = static::normalizeURL($url);

        // return from memory cache if available
        if (isset($cache[$url])) return $cache[$url];

        // try to retrieve from database
        $query = DB::query()->from('wayback_machine')
            ->where('url = ?', [$url])
            ->order('wb_time desc')
            ->limit(1);
        if ($row = $query->fetch()) {
                $result = new WaybackResult(
                    $row['url'],
                    $row['wb_url'],
                    $row['wb_time'],
                    $row['created']
                );
                // memory cache null for expired (might still regenerate below)
                if ($result->expired()) $cache[$url] = null;
                // unexpired but empty results memory cache and immediately return null
                elseif (!$result->wbTime()) return $cache[$url] = null;
                // unexpired non-empty results memory cache and immediately return themselves
                else return $cache[$url] = $result;
        }

        // we might have to actually hit the API now

        // cache null and return immediately if we've made our maximum api calls for this page
        if (static::$checksCount >= Config::get('wayback.max_api_calls')) return $cache[$url] = null;

        // use a lock to rate-limit each API call
        if (!Locking::lock('wayback-api ' . $url, false, 3600)) return $cache[$url] = null;

        // build API request URL
        static::$checksCount++;
        $wb = sprintf(
            'http://archive.org/wayback/available?url=%s',
            urlencode($url)
        );

        // make request with curl
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
            // delete this result if it already exists in the database
            DB::query()->deleteFrom('wayback_machine')
                ->where('uuid = ?', [$result->uuid()])
                ->execute();
            // insert fresh result
            DB::query()->insertInto(
                'wayback_machine',
                [
                    'uuid' => $result->uuid(),
                    'url' => $result->originalURL(),
                    'wb_time' => $result->wbTime() ? $result->wbTime()->getTimestamp() : null,
                    'wb_url' => $result->wbURL(),
                    'created' => $result->created()->getTimestamp(),
                ]
            )->execute();
            // return return value
            return $cache[$url] = $return;
        } else {
            return $cache[$url] = null;
        }
    }
}
