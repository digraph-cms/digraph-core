<?php

namespace DigraphCMS\URL;

use DateTime;
use DateTimeZone;
use DigraphCMS\Cache\Locking;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\Datastore\Datastore;
use DigraphCMS\Datastore\DatastoreGroup;
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
        if (static::isLinkBroken($url)) {
            static::sendNotificationEmail(Context::url(), $url);
            return false;
        } else {
            return true;
        }
    }

    protected static function isLinkBroken($normalizedUrl): ?bool
    {
        $hash = md5($normalizedUrl);
        $status = static::statusStorage()->value($hash);
        // if status is false, this URL has never been checked, add it to the queue and optimistically return a null value to show it's not broken
        if ($status === false) {
            static::statusStorage()->set($hash, 'pending', ['url' => $normalizedUrl]);;
            return null;
        }
        // if it's "pending" then it's still pending a check, and we should optimistically return null (falsey, not broken) until then
        elseif ($status == 'pending') return null;
        // if it's "ok" then it's ok
        elseif ($status == 'ok') return false;
        // otherwise it's an error, return true because this link is broken
        else return true;
    }

    public static function actualUrlStatus($url): bool
    {
        try {
            $ch = CurlHelper::init($url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Wayback isn't in the business of verifying everyone's SSL config
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirects
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0'); // pretend to be a browser
            curl_setopt($ch, CURLOPT_REFERER, Context::url()); // give current page as referer
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
        $data = static::statusStorage()->get($hash);
        if (!$data) return null;
        if ($data->value() == 'pending') return null;
        if ($data->value() == 'ok') return null;
        $apiResult = static::apiStorage()->get($hash);
        // there is no API result, add it as pending so it will be made later
        if (!$apiResult) {
            // add result as pending
            static::apiStorage()->set($hash, 'pending', ['url' => $data->data()['url']]);
            // return no result
            return null;
        }
        // there is an API result, but it doesn't have the necessary info
        elseif (!$apiResult->data()['url'] || !$apiResult->data()['time']) {
            return null;
        }
        // there is an API result, return that
        else return new WaybackResult(
            $data->data()['url'],
            $apiResult->data()['url'],
            $apiResult->data()['time']
        );
    }

    public static function get(string $url): ?WaybackResult
    {
        $url = static::normalizeURL($url);
        if (!$url) return null;
        $hash = md5($url);
        return static::getByHash($hash);
    }

    /**
     * Make an API call to the wayback machine for the given URL, and return
     * null if nothing is found, or an array containing url and time keys
     * for the result.
     * 
     * Returns null if there are no snapshots, false if there was an error.
     *
     * @param string $url normalized URL
     * @return array|false|null
     */
    public static function actualApiCall(string $url)
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
                    'url' => $json['archived_snapshots']['closest']['url'],
                    'time' => DateTime::createFromFormat(
                        'YmdHis',
                        $json['archived_snapshots']['closest']['timestamp'],
                        new DateTimeZone('UTC')
                    )->getTimestamp()
                ];
            } else {
                return null;
            }
        }
        // no valid result returned
        return false;
    }

    public static function setNoNotifyFlag($url, ?URL $context, bool $flag)
    {
        if ($flag) {
            if ($context) Datastore::set('wayback', 'no_notify', md5(serialize([$url, $context->pathString()])), 'blocked', ['url' => $url, 'context' => $context->pathString()]);
            else Datastore::set('wayback', 'no_notify', md5($url), 'blocked', ['url' => $url]);
        } else {
            if ($context) Datastore::delete('wayback', 'no_notify', md5(serialize([$url, $context->pathString()])));
            else Datastore::delete('wayback', 'no_notify', md5($url));
        }
    }

    public static function noNotifyFlag($normalizedUrl, URL $context = null): bool
    {
        if (Datastore::exists('wayback', 'no_notify', md5($normalizedUrl))) return true;
        elseif ($context && Datastore::exists('wayback', 'no_notify', md5(serialize([$normalizedUrl, $context->pathString()])))) return true;
        else return false;
    }

    protected static function sendNotificationEmail(URL $context, $url)
    {
        if (static::noNotifyFlag($url, $context)) return;
        foreach (Config::get('wayback.notify_emails') as $addr) {
            // lock per-recipient
            $lock = Locking::lock(
                'wayback_notification_' . md5(serialize([$context->pathString(), $url, $addr])),
                false,
                Config::get('wayback.notify_frequency')
            );
            if (!$lock) continue;
            // queue email
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

    protected static function statusStorage(): DatastoreGroup
    {
        static $group;
        return $group ?? $group = new DatastoreGroup('wayback', 'status');
    }

    protected static function apiStorage(): DatastoreGroup
    {
        static $group;
        return $group ?? $group = new DatastoreGroup('wayback', 'api');
    }
}
