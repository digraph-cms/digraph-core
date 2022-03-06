<?php

namespace DigraphCMS\Session;

use DateInterval;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\Users;
use donatj\UserAgent\UserAgentParser;

Session::_init();

final class Session
{
    private static $auth;

    public static function _init()
    {
        // if PHP sessions are enabled check there for authentication
        // enabling PHP-managed sessions disables some logging, but is higher performance
        // in some cases, maybe significantly
        if (Config::get('php_session.enabled')) {
            @session_start();
            if ($s = @$_SESSION[Config::get('php_session.key')]) {
                if (Users::get($s['user_uuid'])) {
                    static::setAuth(new PHPAuthentication($s));
                }
            }
        }
        // otherwise use manual authentication cookies and check them against database
        // user must have an auth and token cookie to get authenticated
        else {
            if ($cookie = static::getAuthCookie()) {
                $row = DB::query()
                    ->from('session')
                    ->disableSmartJoin()
                    ->where(
                        'session.id = ? AND session.secret = ? AND session.expires > ?',
                        [intval($cookie['id']), $cookie['secret'], time()]
                    )
                    ->where(
                        'NOT EXISTS (SELECT 1 FROM session_expiration WHERE session_expiration.session_id = session.id)'
                    )
                    ->fetch();
                if ($row) {
                    // valid authentication, set it
                    static::setAuth(new Authentication($row));
                }else {
                    // otherwise clear auth cookie to avoid repetitive DB calls
                    static::clearAuthCookie();
                }
            }
        }
    }

    public static function user(): ?string
    {
        if (static::$auth) {
            return static::$auth->userUUID();
        } else {
            return null;
        }
    }

    public static function authentication(): ?Authentication
    {
        return static::$auth;
    }

    /**
     * Set a new auth for the current session and then check it for anything
     * suspicious that requires deauthentication.
     *
     * @param Authentication $auth
     * @return void
     */
    protected static function setAuth(Authentication $auth)
    {
        static::$auth = $auth;
        // check for different IP address
        if ($auth->ip() != $_SERVER['REMOTE_ADDR']) {
            static::deauthenticate("IP address changed (" . $_SERVER['REMOTE_ADDR'] . ")");
            return;
        }
        // check for different user agent
        if (static::browserPlatform($auth->ua()) != static::browserPlatform()) {
            static::deauthenticate("Browser/OS changed (" . static::fullBrowser() . ")");
            return;
        }
    }

    public static function browserPlatform(string $ua = null): string
    {
        $parser = new UserAgentParser();
        $ua = $parser->parse($ua);
        if ($ua) {
            return $ua->browser() . ' on ' . $ua->platform();
        } else {
            return 'unknown';
        }
    }

    public static function browser(string $ua = null): string
    {
        $parser = new UserAgentParser();
        $ua = $parser->parse($ua);
        if ($ua) {
            return $ua->browser();
        } else {
            return 'unknown';
        }
    }

    public static function platform(string $ua = null): string
    {
        $parser = new UserAgentParser();
        $ua = $parser->parse($ua);
        if ($ua) {
            return $ua->platform();
        } else {
            return 'unknown';
        }
    }

    public static function fullBrowser(string $ua = null): string
    {
        $parser = new UserAgentParser();
        $ua = $parser->parse($ua);
        if ($ua) {
            return $ua->browser() . ' ' . $ua->browserVersion() . ' on ' . $ua->platform();
        } else {
            return 'unknown';
        }
    }

    public static function authenticate(string $user, string $comment, bool $rememberme): Authentication
    {
        // deauthenticate current authorization if one is set
        if (static::$auth) {
            static::deauthenticate('signed in as a different user');
        }
        // decide expiration date
        $expires = new DateTime();
        $expires->add(DateInterval::createFromDateString(Cookies::expiration('auth')));
        // if php session management is enabled, create a new PHPAuthentication
        if (Config::get('php_session.enabled')) {
            return static::$auth = new PHPAuthentication([
                'user_uuid' => $user,
                'comment' => $comment,
                'secret' => static::generateSecret(),
                'created' => time(),
                'expires' => $expires->getTimestamp(),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'ua' => $_SERVER['HTTP_USER_AGENT']
            ]);
        }
        // otherwise use manual authentication cookies and save them in database
        else {
            $row = [
                'user_uuid' => $user,
                'comment' => $comment,
                'secret' => static::generateSecret(),
                'created' => time(),
                'expires' => $expires->getTimestamp(),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'ua' => $_SERVER['HTTP_USER_AGENT']
            ];
            $row['id'] = DB::query()
                ->insertInto('session', $row)
                ->execute();
            static::setAuthCookie(
                $row['id'],
                $row['secret'],
                $rememberme
            );
            return static::$auth = new Authentication($row);
        }
    }

    public static function deauthenticate(string $reason)
    {
        if (static::$auth) {
            static::$auth->deauthenticate($reason);
            static::clearAuthCookie();
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
        Cookies::unset('auth', 'session');
    }

    /**
     * Generates a random 64 character string, consisting of the characters in
     * base 64 encoding (so it's 384 bits)
     *
     * @return string
     */
    protected static function generateSecret(): string
    {
        return URLs::base64_encode(random_bytes(24));
    }
}
