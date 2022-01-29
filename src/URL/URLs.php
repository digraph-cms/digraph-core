<?php

namespace DigraphCMS\URL;

use DigraphCMS\Config;

URLs::_init($_SERVER);

class URLs
{
    public static $protocol, $siteHost, $sitePath;
    public static $context = [];

    /**
     * Called automatically on first use using the environment's real $_SERVER,
     * but can also be called manually using a fake array containing your own
     * values for HTTP_HOST and SCRIPT_NAME.
     *
     * @param array $SERVER
     * @return void
     */
    public static function _init(array $SERVER)
    {
        static::$siteHost = Config::get('urls.site_host') ?? @$SERVER['HTTP_HOST'];
        static::$sitePath =
            Config::get('urls.site_path') ??
            static::$sitePath = preg_replace('/index\.php$/', '', $SERVER['SCRIPT_NAME']);
        static::$sitePath = preg_replace('@/$@', '', static::$sitePath);
        static::$protocol = Config::get('urls.protocol') ? Config::get('urls.protocol') . ':' : '';
    }

    public static function pathToName(string $path, bool $inPageContext = false): string
    {
        if ($inPageContext) {
            if ($basename = basename($path)) {
                $path = $basename;
            }
        }
        $path = preg_replace('/\.[a-z0-9]+$/i', '', $path);
        $path = trim(preg_replace('@[~_.\/]+@', ' ', $path));
        return ucfirst($path);
    }

    /**
     * Encode text to base64 in a way that doesn't use any special/reserved
     * characters, so it can be used without urlencoding.
     *
     * @param string $string
     * @return string
     */
    public static function base64_encode(string $string): string
    {
        return str_replace(['+', '/'], ['-', '_'], base64_encode($string));
    }

    /**
     * Decode base64 text. Works with the normal character set, or this class'
     * base64_encode() method output.
     *
     * @param string $string
     * @return string
     */
    public static function base64_decode(string $string): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }

    /**
     * Begin a "context" which will be used when parsing partial URLs, such as
     * relative paths, or query-only URL strings. For example, setting the
     * context to `[site]/foo/bar?a=b` would allow tricks like:
     * 
     *  * `..` => `[site]/`
     *  * `?z=b` => `[site]/foo/bar?z=b`
     *  * `&z=b` => `[site]/foo/bar?a=b&z=b`
     *
     * @param URL $context
     * @return void
     */
    public static function beginContext(URL $context): void
    {
        static::$context[] = $context;
    }

    /**
     * Retrieve the current context URL, returns the site root if no context is
     * currently active.
     *
     * @return URL
     */
    public static function context(): URL
    {
        if (static::$context) {
            return end(static::$context);
        } else {
            return new URL(static::site() . '/');
        }
    }

    /**
     * Discard the current context URL and make whatever the previous context
     * was the new current context.
     *
     * @return void
     */
    public static function endContext(): void
    {
        array_pop(static::$context);
    }

    /**
     * Clear the context stack, making the site root the current context.
     *
     * @return void
     */
    public static function clearContext(): void
    {
        static::$context = [];
    }

    /**
     * Current site URL, without a trailing slash because the trailing slash
     * here is the leading slash of the site path.
     *
     * @return string
     */
    public static function site(): string
    {
        return static::$protocol . '//' . static::$siteHost . static::$sitePath;
    }

    public static function siteHost(): string
    {
        return static::$siteHost;
    }

    public static function sitePath(): string
    {
        return static::$sitePath;
    }

    public static function siteProtocol(): string
    {
        return Config::get('urls.protocol') ?? (static::isHTTPS() ? 'https' : 'http');
    }

    protected static function isHTTPS()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }
}
