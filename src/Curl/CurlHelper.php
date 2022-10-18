<?php

namespace DigraphCMS\Curl;

use DigraphCMS\Config;

/**
 * Helper class for smoothing out curl inconsistencies and misconfigurations.
 * Can be configured to use its own internal CA certificates file, for 
 * situations in which the server's certificates are misconfigured or missing.
 */
class CurlHelper
{
    protected static $lastError = null;
    protected static $lastErrorNumber = null;

    public static function get(string $url): ?string
    {
        // reset and get a handle
        static::$lastError = null;
        static::$lastErrorNumber = null;
        $handle = static::init($url);
        if (!$handle) return null;
        // set options
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        // execute and return data, store errors if necessary
        $data = curl_exec($handle);
        if ($data === false) {
            static::$lastError = curl_error($handle);
            static::$lastErrorNumber = curl_errno($handle);
            curl_close($handle);
            return null;
        }
        curl_close($handle);
        return $data;
    }

    public static function error(): ?string
    {
        return static::$lastError;
    }

    public static function errorNumber(): ?int
    {
        return static::$lastErrorNumber;
    }

    public static function caFile(): string
    {
        return Config::get('curl.fallback_cafile') ?? realpath(__DIR__ . '/cacert.pem');
    }

    /**
     * @param string $url
     * @return resource|null
     */
    public static function init(string $url)
    {
        // set up handle
        $handle = curl_init($url);
        if (!$handle) return null;
        // configure fallback CA file if configured such
        if (Config::get('curl.use_fallback_cafile')) curl_setopt(
            $handle,
            CURLOPT_CAINFO,
            static::caFile()
        );
        // return handle
        return $handle;
    }
}
