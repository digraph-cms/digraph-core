<?php

namespace DigraphCMS\URL;

use DigraphCMS\Config;

class URLs
{
    public static $siteHost, $sitePath;
    public static $context = [];

    /**
     * Called automatically on first use using the environment's real $_SERVER,
     * but can also be called manually using a fake array containing your own
     * values for HTTP_HOST and SCRIPT_NAME.
     *
     * @param array $SERVER
     * @return void
     */
    public static function __init(array $SERVER)
    {
        self::$siteHost = @$SERVER['HTTP_HOST'];
        self::$sitePath = preg_replace('/index\.php$/', '', $SERVER['SCRIPT_NAME']);
        self::$sitePath = preg_replace('@/$@', '', self::$sitePath);
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
        self::$context[] = $context;
    }

    /**
     * Retrieve the current context URL, returns the site root if no context is
     * currently active.
     *
     * @return URL
     */
    public static function context(): URL
    {
        if (self::$context) {
            return end(self::$context);
        } else {
            return new URL(self::site() . '/');
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
        array_pop(self::$context);
    }

    /**
     * Clear the context stack, making the site root the current context.
     *
     * @return void
     */
    public static function clearContext(): void
    {
        self::$context = [];
    }

    /**
     * Current site URL, without a trailing slash because the trailing slash
     * here is the leading slash of the site path.
     *
     * @return string
     */
    public static function site(): string
    {
        return Config::get('urls.protocol') . '//' . self::$siteHost . self::$sitePath;
    }

    public static function sitePath(): string
    {
        return self::$sitePath;
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

URLs::__init($_SERVER);
