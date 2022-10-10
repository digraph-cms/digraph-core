<?php

namespace DigraphCMS\URL;

use DateTime;
use DateTimeZone;
use DigraphCMS\Cache\CacheNamespace;
use DigraphCMS\Cache\Locking;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\UI\Templates;

class WaybackMachine
{
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
     * response indicates an error.
     * 
     * NOTE: May return true without checking if URL isn't parsed properly, if
     * system is disabled, or if a check for the given URL is still pending.
     *
     * @param string $url
     * @return boolean
     */
    public static function check(string $url): bool
    {
        // active check
        if (!static::active()) return true;
        // normalize URL
        $url = static::normalizeURL($url);
        if (!$url) return true;
        // call other method to actually check status
        $context = Context::url()->__toString();
        if (static::checkUrlStatus($url)) {
            return true;
        } else {
            static::sendNotificationEmail($context, $url);
            return false;
        }
    }

    protected static function checkUrlStatus($url): bool
    {
        return static::checkCache()->getDeferred(
            md5($url),
            function () use ($url) {
                $result = static::doCheckUrlStatus($url);
                // if status is negative, presumptively make an API call so it
                // gets in the deferred execution queue if necessary
                if (!$result) static::get($url);
                return $result;
            }
        ) ?? true;
    }

    protected static function doCheckUrlStatus($url): bool
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
            curl_setopt($ch, CURLOPT_REFERER, Context::url());
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
        } catch (\Throwable $th) {
            return true;
        }
    }

    public static function getByHash(string $hash): ?WaybackResult
    {
        return static::apiCache()->getDeferred($hash);
    }

    public static function get(string $url): ?WaybackResult
    {
        $url = static::normalizeURL($url);
        return static::apiCache()->getDeferred(
            md5($url),
            function () use ($url) {
                $found = static::apiCall($url);
                if (!$found) return null;
                else return new WaybackResult(
                    $url,
                    $found['wb_url'],
                    $found['wb_time'],
                );
            }
        );
    }

    /**
     * Make an API call to the wayback machine for the given URL, and return
     * null if nothing is found, or an array containing wb_url and wb_time keys
     * for the result.
     *
     * @param string $url normalized URL
     * @return array|null
     */
    protected static function apiCall(string $url): ?array
    {
        // build API request URL
        $wb = sprintf(
            'http://archive.org/wayback/available?url=%s',
            urlencode($url)
        );
        // make API request with curl
        $ch = curl_init($wb);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code == 200) {
            $json = json_decode($response, true);
            if ($json['archived_snapshots']) {
                return [
                    'wb_url' => $json['archived_snapshots']['closest']['url'],
                    'wb_time' => DateTime::createFromFormat(
                        'YmdHis',
                        $json['archived_snapshots']['closest']['timestamp'],
                        new DateTimeZone('UTC')
                    )->getTimestamp()
                ];
            }
        }
        // no valid result returned
        return null;
    }

    protected static function sendNotificationEmail($context, $url)
    {
        // this is now the only part that uses the database, and everything else uses the cache
        $lock = Locking::lock(
            'wayback_notification_' . md5($context . $url),
            true,
            Config::get('wayback.notify_frequency')
        );
        if (!$lock) return;
        foreach (Config::get('wayback.notify_emails') as $addr) {
            $email = Email::newForEmail(
                'wayback',
                $addr,
                'Broken link on ' . $context,
                new RichContent(
                    Templates::render(
                        'email/wayback/broken-link.php',
                        [
                            'broken_url' => $url,
                            'context_url' => $context,
                        ]
                    )
                )
            );
            Emails::queue($email);
        }
    }

    protected static function normalizeURL(string $url): ?string
    {
        $url = parse_url($url);
        if (!$url || !@$url['host']) return null;
        $normal = $url['host'];
        if (@$url['port']) {
            $normal .= ':' . $url['port'];
        }
        $normal .= @$url['path'] ?? '/';
        if (@$url['query']) {
            $normal .= '?' . $url['query'];
        }
        $normal = preg_replace('/\/$/', '', $normal);
        return $normal ? $normal : null;
    }

    protected static function checkCache(): CacheNamespace
    {
        static $cache;
        return $cache
            ?? $cache = new CacheNamespace(
                'wayback/check',
                Config::get('wayback.check_ttl'),
                Config::get('wayback.check_ttl') * 10
            );
    }

    protected static function apiCache(): CacheNamespace
    {
        static $cache;
        return $cache
            ?? $cache = new CacheNamespace(
                'wayback/api',
                Config::get('wayback.api_ttl'),
                Config::get('wayback.api_ttl') * 10
            );
    }
}
