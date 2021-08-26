<?php

namespace DigraphCMS\Session;

use DateInterval;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\URL\URLs;

Session::_init();

final class Session
{
    private static $now, $auth;

    public static function _init()
    {
        static::$now = date("Y-m-d H:i:s");
        // user must have an auth and token cookie to get authenticated
        if ($cookie = static::getAuthCookie()) {
            $row = DB::query()
                ->from('sess_auth')
                ->disableSmartJoin()
                ->where(
                    'sess_auth.id = ? AND sess_auth.secret = ? AND sess_auth.expires > ?',
                    [$cookie['id'], $cookie['secret'], static::$now]
                )
                ->where(
                    'NOT EXISTS (SELECT 1 FROM sess_exp WHERE sess_exp.auth = sess_auth.id)',
                )
                ->fetch();
            if ($row) {
                static::setAuth(new Authentication($row));
            }
        }
    }

    public static function user(): ?string
    {
        if (static::$auth) {
            return static::$auth->user()->uuid();
        } else {
            return 'guest';
        }
    }

    public static function authentication(): ?Authentication
    {
        return static::$auth;
    }

    /**
     * Set a new auth for the current session and then check it for anything
     * suspicious that might lead to deauthentication
     *
     * @param Authentication $auth
     * @return void
     */
    protected static function setAuth(Authentication $auth)
    {
        static::$auth = $auth;
        if (Config::get('session.strict_ip_check') && $auth->ip() != $_SERVER['REMOTE_ADDR']) {
            static::deauthenticate("IP address changed");
        } elseif ($auth->ip() != $_SERVER['REMOTE_ADDR'] && $auth->ua() != $_SERVER['HTTP_USER_AGENT']) {
            static::deauthenticate("IP address and user agent changed");
        }
    }

    public static function authenticate(string $user, string $comment, bool $rememberme): Authentication
    {
        if (static::$auth) {
            static::deauthenticate('signed in as a different user');
        }
        $expires = new DateTime();
        $expires->add(DateInterval::createFromDateString(Cookies::expiration('auth')));
        $row = [
            'user' => $user,
            'comment' => $comment,
            'secret' => static::generateSecret(),
            'created' => static::$now,
            'expires' => $expires->format("Y-m-d H:i:s"),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'ua' => $_SERVER['HTTP_USER_AGENT']
        ];
        $row['id'] = DB::query()
            ->insertInto('sess_auth', $row)
            ->execute();
        static::setAuthCookie(
            $row['id'],
            $row['secret'],
            $rememberme
        );
        return static::$auth = new Authentication($row);
    }

    public static function deauthenticate(string $reason)
    {
        if (static::$auth) {
            static::$auth->deauthenticate($reason);
            Cookies::unset('auth', 'session');
        }
    }

    protected static function getAuthCookie(): array
    {
        return Cookies::get('auth', 'session') ?? [];
    }

    protected static function setAuthCookie(int $id, string $secret, bool $rememberme)
    {
        Cookies::set(
            'auth',
            'session',
            ["id" => $id, "secret" => $secret],
            $rememberme
        );
    }

    protected static function clearAuthCookie()
    {
        Cookies::unset('auth', 'auth');
    }

    /**
     * Generates a random 64 character string, consisting of the characters in
     * base 64 encoding (so it's 384 bits)
     *
     * @return string
     */
    protected static function generateSecret(): string
    {
        return URLs::base64_encode(random_bytes(48));
    }
}
