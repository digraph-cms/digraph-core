<?php

namespace DigraphCMS\Security;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Datastore\DatastoreGroup;
use DigraphCMS\DB\DBConnectionException;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use Envms\FluentPDO\Exception;

@session_start();

class Security
{
    /**
     * Secure this request behind a CAPTCHA if user is flagged, operates by
     * bouncing the user to a dedicated CAPTCHA page then back to this URL.
     *
     * @return void
     * @throws RedirectException
     */
    public static function requireSecurityCheck(): void
    {
        if (!static::flagged()) return;
        throw new RedirectException(static::captchaUrl(), targetFrame: '_top');
    }

    public static function captchaUrl(string|null $frame = null): URL
    {
        $url = new URL('/~captcha/');
        $url->setArg('bounce', Context::url()->__toString());
        if ($frame) {
            $url->setArg('frame', $frame);
        }
        return $url;
    }

    /**
     * Check if the current user is flagged in any way, meaning that they should
     * be given a CAPTCHA if required.
     *
     * @return bool
     * @throws Exception
     */
    public static function flagged(bool $guest_default = true): bool
    {
        if (Session::user()) {
            // always respect flags for the authentication/user of users
            if (static::authenticationFlagged() || static::userFlagged()) {
                return true;
            }
            // only respect IP flags if their session is also flagged
            if (static::ipFlagged() && static::sessionFlagged()) {
                return true;
            }
            // users are unflagged by default
            return false;
        } else {
            // unflag only if both IP and session are unflagged
            if (!static::ipFlagged() && !static::sessionFlagged()) {
                return false;
            }
            // guests are flagged by default
            return $guest_default;
        }
    }

    /**
     * Unflag the current user, removing any CAPTCHA requirements for the
     * duration specified in Config::get('captcha.pass_ttl')
     *
     * @return void
     * @throws DBConnectionException
     * @throws Exception
     */
    public static function unflag(): void
    {
        @session_start();
        static::unflagIP();
        static::unflagAuthentication();
        static::unflagUser();
        $_SESSION['digraph_captcha_pass'] = time();
    }

    /**
     * Flag the current user for CAPTCHA verification. This should be done if
     * anything strange happens with this user, such as a failed login attempt
     * or any other sort of suspicious activity.
     *
     * @param string $reason
     *
     * @return void
     * @throws Exception
     */
    public static function flag(string $reason): void
    {
        @session_start();
        static::flagIP(null, $reason);
        static::flagAuthentication(null, $reason);
        static::flagUser(null, $reason);
        static::flagSession();
    }

    public static function flagSession(): void
    {
        $_SESSION['digraph_captcha_pass'] = 0;
    }

    public static function unflagSession(): void
    {
        $_SESSION['digraph_captcha_pass'] = time();
    }

    public static function sessionFlagged(): bool
    {
        return time() >
            intval(@$_SESSION['digraph_captcha_pass']) + Config::get('captcha.pass_ttl');
    }

    public static function ipFlagged(string|null $ip = null): bool
    {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        return static::flaggedIPs()->exists($ip)
            && static::flaggedIPs()->value($ip) != 'passed';
    }

    public static function unflagIP(string|null $ip = null): void
    {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $data = static::flaggedIPs()->get($ip);
        if (!$data) return;
        if ($data->value() == 'passed') return;
        $data->setValue('passed');
        $data->update();
    }

    public static function flagIP(string|null $ip = null, string $reason = 'unspecified'): void
    {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $data = static::flaggedIPs()->get($ip)?->data()->get(null) ?? [];
        $data[] = [
            'reason' => $reason,
            'time' => time(),
            'url' => static::actualUrl(),
        ];
        static::flaggedIPs()->set($ip, 'pending', $data);
    }

    public static function banned(string|null $ip = null, string|User|null $user = null): bool
    {
        // bypass bans for signed-in users
        $user = $user ?? Session::uuid();
        if ($user instanceof User) $user = $user->uuid();
        if ($user != 'guest') return false;
        // check the user's IP for excessive flags
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $key = 'security/ip_bans/' . md5($ip);
        $window = (int)Config::get('security.ip_bans.window');
        return Cache::get(
            $key,
            function () use ($ip, $window): bool {
                $data = static::flaggedIPs()->get($ip);
                if (!$data) return false;
                $time = $data->updated()->getTimestamp();
                if (time() - $time > $window) return false;
                $flags = $data->data()->get(null) ?? [];
                $limit = (int)Config::get('security.ip_bans.limit');
                if (count($flags) < $limit) return false;
                $count = 0;
                $expiry = time() - $window;
                while ($flag = array_pop($flags)) {
                    if ($flag['time'] > $expiry) {
                        $count++;
                        if ($count >= $limit) return true;
                    } else {
                        break;
                    }
                }
                return false;
            },
            $window / 10
        );
    }

    public static function userFlagged(string|User|null $user = null): bool
    {
        if (is_null($user)) $user = Session::uuid();
        if ($user instanceof User) $user = $user->uuid();
        if ($user == 'guest') return false;
        return static::flaggedUsers()->exists($user)
            && static::flaggedUsers()->value($user) != 'passed';
    }

    public static function unflagUser(string|User|null $user = null): void
    {
        if (is_null($user)) $user = Session::uuid();
        if ($user instanceof User) $user = $user->uuid();
        if ($user == 'guest') return;
        $data = static::flaggedUsers()->get($user);
        if (!$data) return;
        if ($data->value() == 'passed') return;
        $data->setValue('passed');
        $data->update();
    }

    public static function flagUser(string|User|null $user = null, string $reason = 'unspecified'): void
    {
        if (is_null($user)) $user = Session::uuid();
        if ($user instanceof User) $user = $user->uuid();
        if ($user == 'guest') return;
        $data = static::flaggedUsers()->get($user)?->data()->get(null) ?? [];
        $data[] = [
            'reason' => $reason,
            'time' => time(),
            'url' => static::actualUrl(),
        ];
        static::flaggedUsers()->set($user, 'pending', $data);
    }

    public static function authenticationFlagged(string|null $authentication_id = null): bool
    {
        $authentication_id = $authentication_id ?? Session::authentication()?->id();
        if (!$authentication_id) return false;
        return static::flaggedAuthentications()->exists($authentication_id)
            && static::flaggedAuthentications()->value($authentication_id) != 'passed';
    }

    public static function unflagAuthentication(string|null $authentication_id = null): void
    {
        $authentication_id = $authentication_id ?? Session::authentication()?->id();
        if (!$authentication_id) return;
        $data = static::flaggedAuthentications()->get($authentication_id);
        if (!$data) return;
        if ($data->value() == 'passed') return;
        $data->setValue('passed');
        $data->update();
    }

    public static function flagAuthentication(string|null $authentication_id = null, string $reason = 'unspecified'): void
    {
        $authentication_id = $authentication_id ?? Session::authentication()?->id();
        if (!$authentication_id) return;
        $data = static::flaggedAuthentications()->get($authentication_id)?->data()->get(null) ?? [];
        $data[] = [
            'reason' => $reason,
            'time' => time(),
            'url' => static::actualUrl(),
        ];
        static::flaggedAuthentications()->set($authentication_id, 'pending', $data);
    }

    /**
     * @return string get the actual URL of the current request, from outside the CMS
     */
    protected static function actualUrl(): string
    {
        return sprintf(
            '%s://%s%s%s',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI'],
            @$_SERVER['QUERY_STRING'] ? '?' . @$_SERVER['QUERY_STRING'] : ''
        );
    }

    protected static function flaggedAuthentications(): DatastoreGroup
    {
        return new DatastoreGroup('security_flags', 'flagged_authentications');
    }

    protected static function flaggedUsers(): DatastoreGroup
    {
        return new DatastoreGroup('security_flags', 'flagged_users');
    }

    protected static function flaggedIPs(): DatastoreGroup
    {
        return new DatastoreGroup('security_flags', 'flagged_ips');
    }
}