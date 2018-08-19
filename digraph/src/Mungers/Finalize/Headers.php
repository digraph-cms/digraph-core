<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\Mungers\Finalize;

use Digraph\Mungers\AbstractMunger;

class Headers extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        // cache control
        $package['response.headers.date'] = gmdate('D, d M Y H:i:s T', time());
        $package['response.headers.cache-control'] = $this->cacheControl($package);
        $package['response.headers.pragma'] = $this->pragma($package);
        if ($ttl = $package['response.ttl']) {
            $package['response.headers.expires'] = gmdate('D, d M Y H:i:s T', time()+$ttl);
        }
        // content-type/encoding
        if ($package['response.mime'] == 'text/html') {
            //include charset for text/html
            $package['response.headers.content-type'] = '${response.mime}; charset=${response.charset}';
        } else {
            //not for other content types
            $package['response.headers.content-type'] = '${response.mime}';
        }
        //content disposition/name
        $package['response.headers.content-disposition'] = '${response.disposition}';
        if ($package['response.filename']) {
            $fn = urlencode($package['response.filename']);
            $package['response.headers.content-disposition'] = $package['response.headers.content-disposition']."; filename=\"$fn\"";
        }
        //redirection
        if ($package['response.redirect']) {
            $package['response.headers.location'] = '${response.redirect}';
        }
        //canonical url
        if ($package['response.canonicalurl']) {
            $package['response.headers.link'] = '<${response.canonicalurl}>; rel="canonical"';
        }
    }

    protected function pragma($package)
    {
        if ($package['request.namespace'] == 'public') {
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
        if ($package['request.namespace'] == 'public') {
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
