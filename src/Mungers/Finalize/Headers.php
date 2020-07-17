<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Finalize;

use Digraph\Mungers\AbstractMunger;
use Digraph\Mungers\Package;
use Flatrr\FlatArray;

class Headers extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        //record memory use
        $package['peak_memory_use'] = (round(memory_get_peak_usage() / 1024 / 1024 * 100) / 100) . 'MB';
        /*
        Set up headers
         */
        $headers = new FlatArray();
        // cache control
        $headers['Date'] = gmdate('D, d M Y H:i:s T', time());
        $headers['Cache-Control'] = $this->cacheControl($package);
        $headers['Pragma'] = $this->pragma($package);
        if ($ttl = $package['response.browserttl']) {
            $headers['Expires'] = gmdate('D, d M Y H:i:s T', time() + $ttl);
        } else {
            $headers['Expires'] = gmdate('D, d M Y H:i:s T', 0);
        }
        // last-modified
        if (!$package['response.last-modified']) {
            $package['response.last-modified'] = $this->generateLastModified($package);
        }
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
            $fn = str_replace('+', ' ', $fn);
            $headers['Content-Disposition'] = $headers['Content-Disposition'] . "; filename=\"$fn\"";
        }
        //redirection
        if ($package['response.redirect']) {
            $headers['Location'] = '${response.redirect}';
        }
        //canonical url
        $url = $package->url();
        unset($url['args.digraph_url']);
        unset($url['args.digraph_redirect_count']);
        $headers['Link'] = '<' . $url . '>; rel="canonical"';
        //merge into package, not overwriting so that previous mungers can set headers
        $package->merge($headers->get(), 'response.headers');
    }

    protected function generateLastModified(Package $package)
    {
        $best = 0;
        foreach ($package['cachetags'] ?? [] as $id) {
            if ($ob = $package->cms()->read($id, false)) {
                if ($ob['dso.modified.date'] > $best) {
                    $best = $ob['dso.modified.date'];
                }
            }
        }
        return $best;
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
        if ($ttl = $package['response.browserttl']) {
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
