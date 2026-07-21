<?php

namespace DigraphCMS\URL;

use DateTime;
use DateTimeZone;
use DigraphCMS\Cache\RateLimit;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\Datastore\Datastore;
use DigraphCMS\Datastore\DatastoreGroup;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\ExceptionLog;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\UI\Templates;
use Envms\FluentPDO\Exception;

class WaybackMachine
{

    /** @var bool|null */
    protected static $active = null;

    /** @var bool */
    protected static $notifications = true;

    /** @var string[] */
    public static array $log = [];

    /**
     * called by CoreCronSubscriber to automatically clean up old Wayback
     * data, so that it's not constantly making checks for URLs that aren't
     * even referenced any more.
     * 
     * @return void 
     * @throws Exception 
     */
    public static function cleanup(): void
    {
        // only keep status and API data for 90 days
        static::statusStorage()->expire(time() - (86400 * 90));
        static::apiStorage()->expire(time() - (86400 * 90));
        // keep everything else for a year, which means pages and no_notify flags do expire yearly
        Datastore::expire('wayback', null, time() - 86400 * 365);
    }

    public static function activate(): void
    {
        if (static::active() === false)
            static::$active = true;
    }

    public static function deactivate(): void
    {
        if (static::active() === true)
            static::$active = false;
    }

    public static function active(): bool
    {
        return static::$active ?? Config::get('wayback.active');
    }

    public static function enableNotifications(): void
    {
        static::$notifications = true;
    }

    public static function disableNotifications(): void
    {
        static::$notifications = false;
    }

    public static function notifications(): bool
    {
        return static::$notifications;
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
     * @param boolean $skipNotification
     * @return boolean
     */
    public static function check(string $url, bool $skipNotification = false): bool
    {
        // active check
        if (!static::active())
            return true;
        // skip check if url is in this site
        if (str_starts_with(strtolower($url), URLs::site()))
            return true;
        // skip check if url is already a wayback URL
        if (str_starts_with(strtolower($url), 'http://web.archive.org/web/'))
            return true;
        if (str_starts_with(strtolower($url), 'https://web.archive.org/web/'))
            return true;
        // normalize URL
        $url = static::normalizeURL($url);
        if (!$url)
            return true;
        // call other method to actually check status
        if (static::isLinkBroken($url)) {
            if (!$skipNotification)
                static::sendNotificationEmail(Context::url(), $url);
            return false;
        } else {
            return true;
        }
    }

    protected static function isLinkBroken(string $normalizedUrl): ?bool
    {
        $status = static::statusStorage()->value($normalizedUrl);
        // if status is false, this URL has never been checked, add it to the
        // queue and optimistically return a null value to show it's not known
        // to be broken
        if ($status === false) {
            static::statusStorage()->set($normalizedUrl, 'pending', ['url' => $normalizedUrl]);;
            return null;
        }
        // if it's "pending" then it's still pending a check, and we should optimistically return null (falsey, not broken) until then
        elseif ($status == 'pending')
            return null;
        // if it's "ok" then it's ok
        elseif ($status == 'ok')
            return false;
        // otherwise it's an error, return true because this link is broken
        else
            return true;
    }

    public static function actualUrlStatus(string $url): bool
    {
        static::$log[] = $url;
        try {
            $ch = CurlHelper::init($url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Wayback isn't in the business of verifying everyone's SSL config
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirects
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0'); // pretend to be a browser
            curl_setopt($ch, CURLOPT_REFERER, Context::url()->__toString()); // give current page as referer
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $errno = curl_errno($ch);
            curl_close($ch);
            if ($code >= 200 && $code < 400) {
                return true;
            } elseif ($code === 401) {
                return true;
            } elseif ($errno == 28) {
                static::$log[] = 'Timeout';
                return true;
            } elseif ($code == 403) {
                static::$log[] = 'HTTP code: ' . $code;
                static::$log[] = 'Curl error number: ' . $errno;
                static::$log[] = 'Curl error: ' . curl_error($ch);
                static::$log[] = 'Optimistically calling 403 success';
                return true;
            } else {
                static::$log[] = 'HTTP code: ' . $code;
                static::$log[] = 'Curl error number: ' . $errno;
                static::$log[] = 'Curl error: ' . curl_error($ch);
                return false;
            }
        } catch (\Throwable $th) {
            static::$log[] = get_class($th);
            static::$log[] = $th->getMessage();
            return true;
        }
    }

    public static function get(string $url): ?WaybackResult
    {
        $url = static::normalizeURL($url);
        if (!$url)
            return null;
        $data = static::statusStorage()->get($url);
        if (!$data)
            return null;
        if ($data->value() == 'pending')
            return null;
        if ($data->value() == 'ok')
            return null;
        $apiResult = static::apiStorage()->get($url);
        // there is no API result, add it as pending so it will be made later
        if (!$apiResult) {
            // add result as pending
            static::apiStorage()->set($url, 'pending', ['url' => $data->data()['url']]);
            // return no result
            return null;
        }
        // there is an API result, but it doesn't have the necessary info
        elseif (!$apiResult->data()['url'] || !$apiResult->data()['time']) {
            return null;
        }
        // there is an API result, return that
        else
            return new WaybackResult(
                $data->data()['url'],
                $apiResult->data()['url'],
                $apiResult->data()['time'],
            );
    }

    /**
     * Make an API call to the wayback machine for the given URL, and return
     * null if nothing is found, or an array containing url and time keys
     * for the result.
     * 
     * Returns null if there are no snapshots, false if there was an error.
     *
     * @param string $url normalized URL
     * @return array<string,mixed>|false|null
     */
    public static function actualApiCall(string $url): array|bool|null
    {
        // build API request URL
        $wb = sprintf(
            'http://archive.org/wayback/available?url=%s',
            urlencode($url),
        );
        // make API request with curl
        $ch = curl_init($wb);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0',
        );
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code == 200) {
            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            if (
                is_array($json)
                && array_key_exists('archived_snapshots', $json)
                && is_array($json['archived_snapshots'])
            ) {
                if (!$json['archived_snapshots'] || !array_key_exists('closest', $json['archived_snapshots']))
                    return null;
                return [
                    'url'  => $json['archived_snapshots']['closest']['url'],
                    'time' => DateTime::createFromFormat(
                        'YmdHis',
                        $json['archived_snapshots']['closest']['timestamp'],
                        new DateTimeZone('UTC'),
                    )->getTimestamp(),
                ];
            } else {
                ExceptionLog::logMessage(
                    "Possibly malformed wayback response",
                    [
                        'url'      => $url,
                        'response' => $response,
                        'code'     => $code,
                        'json'     => $json,
                    ],
                );
                return null;
            }
        }
        // no valid result returned
        ExceptionLog::logMessage(
            "Wayback error response",
            [
                'url'      => $url,
                'response' => $response,
                'code'     => $code,
            ],
        );
        return false;
    }

    public static function setNoNotifyFlag(string $url, ?URL $context, bool $flag): void
    {
        if ($flag) {
            if ($context)
                Datastore::set('wayback', 'no_notify', md5(serialize([$url, $context->pathString()])), 'blocked', ['url' => $url, 'context' => $context->pathString()]);
            else
                Datastore::set('wayback', 'no_notify', md5($url), 'blocked', ['url' => $url]);
        } else {
            if ($context)
                Datastore::delete('wayback', 'no_notify', md5(serialize([$url, $context->pathString()])));
            else
                Datastore::delete('wayback', 'no_notify', md5($url));
        }
    }

    public static function noNotifyFlag(string $normalizedUrl, URL|null $context = null): bool
    {
        if (Datastore::exists('wayback', 'no_notify', md5($normalizedUrl)))
            return true;
        elseif ($context && Datastore::exists('wayback', 'no_notify', md5(serialize([$normalizedUrl, $context->pathString()]))))
            return true;
        else
            return false;
    }

    protected static function sendNotificationEmail(URL $context, string $url): void
    {
        if (!static::notifications())
            return;
        if (static::noNotifyFlag($url, $context))
            return;
        foreach (Config::get('wayback.notify_emails') as $addr) {
            $id = md5(serialize([$context->pathString(), $url, $addr]));
            RateLimit::run(
                'wayback_notification',
                $id,
                Config::get('wayback.notify_frequency'),
                function () use ($context, $url, $addr) {
                    // queue email
                    $email = Email::newForEmail(
                        'wayback',
                        $addr,
                        'Broken link on ' . $context,
                        new RichContent(
                            Templates::render(
                                'email/wayback/broken-link.php',
                                [
                                    'broken_url'  => $url,
                                    'context_url' => $context,
                                ],
                            ),
                        ),
                    );
                    Emails::queue($email);
                }
            );
        }
    }

    /**
     * Strip URL to lowercase host/path/query only, ignoring all other parts. Returns null on error.
     */
    protected static function normalizeURL(string $url_string): ?string
    {
        $url = parse_url($url_string);
        if (!$url || !@$url['host'])
            return null;
        // get host/port to start normalized URL
        $normal = $url['host'];
        if (@$url['port']) {
            $normal .= ':' . $url['port'];
        }
        // add path to normalized URL
        $normal .= @$url['path'] ? $url['path'] : '/';
        // add query to normalized URL
        if (@$url['query']) {
            $normal .= '?' . $url['query'];
        }
        return $normal;
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
