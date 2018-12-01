<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Finalize;

use Digraph\Mungers\AbstractMunger;
use Flatrr\FlatArray;

class Headers extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        //record memory use
        $package['peak_memory_use'] = (round(memory_get_peak_usage()/1024/1024*100)/100).'MB';
        /*
        Set up headers
         */
        $headers = new FlatArray();
        // cache control
        $headers['Date'] = gmdate('D, d M Y H:i:s T', time());
        $headers['Cache-Control'] = $this->cacheControl($package);
        $headers['Pragma'] = $this->pragma($package);
        if ($ttl = $package['response.ttl']) {
            $headers['Expires'] = gmdate('D, d M Y H:i:s T', time()+$ttl);
        } else {
            $headers['Expires'] = gmdate('D, d M Y H:i:s T', 0);
        }
        // last-modified
        if ($package['response.last-modified']) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s T', $package['response.last-modified']);
        }
        // Content-Type/encoding
        if ($package['response.mime'] == 'text/html') {
            //include charset for text/html
            $headers['Content-Type'] = '${response.mime}; charset=${response.charset}';
        } else {
            //not for other content types
            $headers['Content-Type'] = '${response.mime}';
        }
        //content disposition/name
        $headers['Content-Disposition'] = '${response.disposition}';
        if ($package['response.filename']) {
            $fn = urlencode($package['response.filename']);
            $headers['Content-Disposition'] = $headers['Content-Disposition']."; filename=\"$fn\"";
        }
        //redirection
        if ($package['response.redirect']) {
            $headers['Location'] = '${response.redirect}';
        }
        //merge into package, not overwriting so that previous mungers can set headers
        $package->merge($headers->get(), 'response.headers');
    }

    protected function pragma($package)
    {
        if ($package['request.namespace'] == 'public' || $package['response.headers.pragma'] == 'public') {
            return 'public';
        } else {
            return 'no-cache';
        }
    }

    protected function cacheControl($package)
    {
        //expiration/cache control
        $cacheControl = array();
        //privacy
        if ($package['request.namespace'] == 'public' || $package['response.headers.pragma'] == 'public') {
            $cacheControl['public'] = true;
        } else {
            $cacheControl['private'] = true;
            $cacheControl['must-revalidate'] = true;
        }
        //cache ttl
        if ($ttl = $package['response.ttl']) {
            $cacheControl['max-age'] = $ttl;
        }
        //finish cache-control
        $output = array();
        foreach ($cacheControl as $key => $value) {
            if ($value === false) {
                continue;
            } elseif ($value === true) {
                $value = $key;
            } else {
                $value = "$key=$value";
            }
            $output[$key] = $value;
        }
        return implode(', ', $output);
    }

    protected function doConstruct($name)
    {
    }
}
