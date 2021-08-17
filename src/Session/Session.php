<?php

namespace DigraphCMS\Session;

Session::__init();

class Session
{
    /**
     * Number of seconds for session authorization to time out. Allows configurable auth
     * expirations faster (but not slower) than the expiration time in cookieParams().
     * Default is one hour.
     * 
     * Overridden with constant SESSION_AUTH_TIMEOUT
     *
     * @return integer
     */
    public static function authTimeout(): int
    {
        return defined('SESSION_AUTH_TIMEOUT') ? SESSION_AUTH_TIMEOUT : 3600;
    }

    /**
     * Dump all session data as a raw array.
     *
     * @return array
     */
    public static function dump(): array
    {
        return $_SESSION;
    }

    /**
     * Set a value, which unless $immediate is true will be queued for after
     * page is finished.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a value, as it stood during the Session was last read.
     *
     * @param string $key
     * @return mixed
     */
    public static function get(string $key)
    {
        return @$_SESSION[$key];
    }

    /**
     * Unset a value, which unless $immediate is true will be queued for after
     * page is finished.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function unset(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Use a glob-style search to retrieve all key/value pairs in which the 
     * key matches the supplied glob. Different numbers of `*` characters match
     * different levels of specificity.
     * 
     * `***` matches anything string
     * 
     * `**` matches anything without passing `:` characters (which are used to
     * delineate something along the lines of namespaces)
     * 
     * `*` matches anything without passing `:` or `/` characters (which allows
     * `/` to be used something like directories in a normal glob
     *
     * @param string $glob
     * @return array
     */
    public static function glob(string $glob): array
    {
        $pattern = preg_quote($glob, '/');
        // *** matches anything
        $pattern = str_replace('\\*\\*\\*', '.*', $pattern);
        // ** matches anything but a :
        $pattern = str_replace('\\*\\*', '[^\:]*', $pattern);
        // * matches anything but : and /
        $pattern = str_replace('\\*', '[^\:\/]*', $pattern);
        $result = [];
        foreach ($_SESSION as $key => $value) {
            if (preg_match("/$pattern/", $key)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Set user to false, and attempt to immediately get a lock on
     * the session and save the change.
     *
     * @return void
     */
    public static function deauthorize()
    {
        self::setUser('guest');
    }

    /**
     * Return the current user.
     *
     * @return string
     */
    public static function user(): string
    {
        return self::get('user');
    }

    /**
     * Set the current user (and attempt to immediately get a lock
     * and save the change)
     *
     * @param string $user
     * @return void
     */
    public static function setUser(string $user)
    {
        if ($user != self::user()) {
            self::set('user_history/' . time(), $user);
        }
        self::set('user', $user);
    }

    /**
     * Retrieve a list of all remote() values (IP/proxy, UA) that have
     * been seen for this session, indexed by timestamp.
     *
     * @return array
     */
    public static function remoteHistory(): array
    {
        $return = [];
        foreach (self::glob('remote_history/*') as $key => $value) {
            $return[substr($key, 15)] = $value;
        }
        return $return;
    }

    /**
     * Retrieve a list of all user() values that have been seen for this 
     * session, indexed by timestamp.
     *
     * @return array
     */
    public static function userHistory(): array
    {
        $return = [];
        foreach (self::glob('user_history/*') as $key => $value) {
            $return[substr($key, 12)] = $value;
        }
        return $return;
    }

    /**
     * Initialize session. Called automatically when class is loaded.
     *
     * @return void
     */
    public static function __init()
    {
        // start/grab session
        @session_start();
        // initialize data
        if (!@$_SESSION) {
            $_SESSION = self::__initData();
        }
        // if remote is different, deauthorize and save it into remote_history
        $remote = self::remote();
        if ($remote != $_SESSION['remote']) {
            // record new remote/history
            $_SESSION['remote_history/' . time()] = $_SESSION['remote'];
            $_SESSION['remote'] = $remote;
            // manually deauthorize
            if ($_SESSION['user']) {
                $_SESSION['user_history/' . time()] = $_SESSION['user'] = 'guest';
            }
        }
        // deauthorize if touch is too long ago
        if ($_SESSION['user']) {
            if (time() - $_SESSION['touch'] > self::authTimeout()) {
                $_SESSION['user_history/' . time()] = $_SESSION['user'] = 'guest';
            }
        }
        // set touch time
        $_SESSION['touch'] = time();
        $_SESSION['auth_expires'] = $_SESSION['touch'] + self::authTimeout();
    }

    /**
     * Generate the data to initially populate session
     *
     * @return array
     */
    protected static function __initData(): array
    {
        $time = time();
        $remote = self::remote();
        return [
            'start' => $time,
            'touch' => 0,
            'auth_expires' => 0,
            'user' => 'guest',
            'remote' => $remote,
            'remote_history/' . $time => $remote,
            'user_history/' . $time => 'guest'
        ];
    }

    /**
     * Retrieve a namespace for isolating and easily working with a subset of session
     * variables, as well as controlling whether they should be keyed to be accessible
     * only to the currently-authorized user.
     *
     * @param string $name
     * @param boolean $unprotected set to true to make this namespace available to all users (in the same session)
     * @return SessionNamespace
     */
    public static function namespace(string $name, $unprotected = false): SessionNamespace
    {
        $name = self::namespacePrefix($unprotected) . $name;
        return new SessionNamespace($name);
    }

    /**
     * Flash namespaces are similar to a normal namespace, but have additional features
     * for storing both a "current" and "next" set of values, and choosing when to advance
     * "next" to replace "current"
     * 
     * Useful for things like flashing notifications to users on their *next* pageview
     *
     * @param string $name
     * @param boolean $unprotected set to true to make this namespace available to all users (in the same session)
     * @return SessionNamespace
     */
    public static function flashNamespace(string $name, $unprotected = false): FlashSessionNamespace
    {
        $name = self::namespacePrefix($unprotected) . $name;
        return new FlashSessionNamespace($name);
    }

    /**
     * Generate a prefix for a namespace name, including a hash of the user
     * if necessary.
     *
     * @param boolean $unprotected
     * @return string
     */
    protected static function namespacePrefix(bool $unprotected): string
    {
        $prefix = '_ns:';
        if ($unprotected) {
            $prefix .= '_up:';
        } elseif (self::user()) {
            $prefix .= md5(serialize(self::user())) . ':';
        } else {
            $prefix .= '_guest:';
        }
        return $prefix;
    }

    /**
     * Current remote client's IP, forwarding information, and user agent.
     *
     * @return array
     */
    public static function remote(): array
    {
        return [
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
            'HTTP_CLIENT_IP' => @$_SERVER['HTTP_CLIENT_IP'],
            'HTTP_X_FORWARDED_FOR' => @$_SERVER['HTTP_X_FORWARDED_FOR'],
            'HTTP_USER_AGENT' => @$_SERVER['HTTP_USER_AGENT']
        ];
    }
}
