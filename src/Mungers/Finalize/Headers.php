<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Finalize;

use Digraph\Mungers\AbstractMunger;
use Digraph\Mungers\Package;
use Flatrr\FlatArray;

class Headers extends AbstractMunger
{
    protected function doMunge($package)
    {
        //record memory use
        $package['peak_memory_use'] = (round(memory_get_peak_usage() / 1024 / 1024 * 100) / 100) . 'MB';
        /*
        Set up headers
         */
        $headers = new FlatArray();
        // cache control
        $headers['cache-control'] = $this->cacheControl($package);
        // last-modified
        $headers['last-modified'] = $this->lastModified($package);
        // content-type/encoding
        if ($package['response.mime'] == 'text/html') {
            //include charset for text/html
            $headers['content-type'] = '${response.mime}; charset=${response.charset}';
        } else {
            //not for other content types
            $headers['content-type'] = '${response.mime}';
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
        if ($url->__toString() != $package['request.actualurl']) {
            unset($url['args.digraph_url']);
            unset($url['args.digraph_redirect_count']);
            $headers['link'] = '<' . $url . '>; rel="canonical"';
        }
        //etag
        $headers['etag'] = $package->hash('response');
        //merge into package, not overwriting so that previous mungers can set headers
        $package->merge($headers->get(), 'response.headers');
    }

    protected function lastModified(Package $package) {
        $result = $package['response.last-modified'];
        if ($result === false) {
            return false;
        }
        if ($result === null) {
            $result = $this->generateLastModified($package);
        }
        if ($result === null) {
            $result = time();
        }
        if ($result) {
            return gmdate('D, d M Y H:i:s T', $result);
        }else {
            return false;
        }
    }

    protected function generateLastModified(Package $package)
    {
        $best = null;
        foreach ($package['cachetags'] ?? [] as $id) {
            if ($ob = $package->cms()->read($id, false)) {
                if ($ob['dso.modified.date'] > $best) {
                    $best = $ob['dso.modified.date'];
                }
            }
        }
        return $best;
    }

    protected function cacheControl($package)
    {
        //expiration/cache control
        $cache = [];
        //cacheability
        switch ($package['response.cache.cacheability']) {
            case 'public':
                $cache['public'] = true;
                break;
            case 'private':
                $cache['private'] = true;
                break;
            case 'no-cache':
                $cache['no-cache'] = true;
                unset($package['response.cache.immutable']);
                break;
            case 'no-store':
                $cache['no-store'] = true;
                unset($package['response.cache.max-age']);
                break;
        }
        //max-age
        if (intval($package['response.cache.max-age'])) {
            $cache['max-age'] = intval($package['response.cache.max-age']);
        }
        //immutable
        if ($package['response.cache.immutable']) {
            $cache['immutable'] = true;
        }
        //finish cache-control
        $output = [];
        foreach ($cache as $key => $value) {
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
