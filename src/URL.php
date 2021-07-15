<?php

namespace DigraphCMS;

/**
 * A URL represents a URL within the site defined by URL::$siteHost and 
 * URL::$sitePath, and including the in-site path and query parameters. A key
 * feature of URLs is that they can be printed as a string.
 */
class URL
{
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

    // static variables and methods

    public static $siteHost, $sitePath;
    public static $context = [];

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
        return '//' . self::$siteHost . self::$sitePath;
    }

    // instance variables and methods

    protected $path, $query;

    /**
     * Parse a string and attempt to turn it into an in-site URL. Accepts both
     * relative and leading-slash site-absolute URLs. Also accepts full query
     * strings like `?a=b` or partial ones like `&a=b`. Can also traverse using
     * `..` directory names, and such traversals will be kept within the site
     * root path.
     * 
     * Also accepts full URLs, as long as they are in-site.
     * 
     * ! An exception will be thrown if a not-in-site full URL is provided
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        // replace empty url with context
        if ($url == '' || $url == '/') {
            $url = self::context()->directory()->__toString();
        }
        // prefix with context for empty or query-only strings
        if (!$url || $url[0] == '?') {
            $url = self::context() . $url;
        }
        // merge in query for partial query strings
        if ($url[0] == '&') {
            parse_str(substr($url, 1), $query);
            $url = self::context();
            $query = array_merge($url->query(), $query);
            $url->query($query);
            $url = $url->__toString();
        }
        // prefix with site URL if it starts with /
        if ($url[0] == '/' && @$url[1] != '/') {
            $url = self::site() . $url;
        }
        // otherwise add it to the context directory
        elseif ($url[0] != '/') {
            $url = self::context()->directory() . $url;
        }
        // strip protocol
        $url = preg_replace('@^(https?:)?//@', '//', $url);
        // parse
        $parsed = parse_url($url);
        // URL must be inside site
        if (substr($url, 0, strlen(self::site())) != self::site()) {
            throw new \Exception('Trying to parse URL ' . $url . ' failed, URLs must be in site ' . self::site());
        }
        // pull out in-site path
        $this->path(substr($parsed['path'], strlen(self::$sitePath)));
        // pull out query
        if (@$parsed['query']) {
            parse_str($parsed['query'], $this->query);
            ksort($this->query);
        } else {
            $this->query = [];
        }
    }

    /**
     * Get the directory portion of the URL path, without any trailing filename
     *
     * @return URL
     */
    public function directory(): URL
    {
        $url = clone $this;
        $url->query([]);
        if (!preg_match('@/$@', $url->path())) {
            $url->path(dirname($url->path()));
            if ($url->path() != '/') {
                $url->path($url->path() . '/');
            }
        }
        return $url;
    }

    public function __toString(): string
    {
        return $this->site() . $this->path() . $this->queryString();
    }

    protected function queryString(): string
    {
        if ($this->query) {
            return '?' . http_build_query($this->query);
        } else {
            return '';
        }
    }

    /**
     * Get or set the in-site path of this URL
     *
     * @param string $path
     * @return string
     */
    public function path(string $path = null): string
    {
        if ($path !== null) {
            // make sure trailing .. has a trailing slash
            $path = preg_replace('@/\.\.$@', '/../', $path);
            // resolve ..s
            $path = explode('/', $path);
            foreach ($path as $i => $e) {
                if ($e === '..') {
                    $path[$i] = false;
                    while ($i >= 0) {
                        if ($path[$i] !== false) {
                            $path[$i] = false;
                            break;
                        } else {
                            $i--;
                        }
                    }
                }
            }
            $path = array_filter($path, function ($e) {
                return $e !== false;
            });
            $path = implode('/', $path);
            // normalize to have leading slash
            if (@$path[0] != '/') {
                $path = '/' . $path;
            }
            // save
            $this->path = $path;
        }
        return $this->path;
    }

    /**
     * Get or set the entire query string as an array
     *
     * @param array $query
     * @return array
     */
    public function query(array $query = null): array
    {
        if ($query !== null) {
            ksort($query);
            $this->query = $query;
        }
        return $this->query;
    }
}

URL::__init($_SERVER);
