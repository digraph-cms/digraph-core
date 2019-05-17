<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Setup;

use Digraph\Mungers\AbstractMunger;

class Locate extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge(&$package)
    {
        $url = $package->url();
        if ($noun = $package->cms()->read($url['noun'], false)) {
            //we're using a canonical url
            $package->noun($noun);
        } else {
            //we appear to not be using a canonical url
            if ($url->pathString() == '') {
                $slugs = [['home',null]];
            } else {
                $slugs = [];
                $new = [trim($url->pathString(), "/ \t\n\r\0\x0B"),null];
                $slugs[md5(serialize($new))] = $new;
                if (strpos($url->pathString(), '/') !== false) {
                    $path = explode('/', $url->pathString());
                    $verb = array_pop($path);
                    if (!$verb) {
                        $verb = null;
                    }
                    $new = [trim(implode('/', $path), "/ \t\n\r\0\x0B"),$verb];
                    $slugs[md5(serialize($new))] = $new;
                }
            }
            //search for possible slug matches
            $opts = [];
            foreach ($slugs as $slug) {
                list($slug, $verb) = $slug;
                foreach ($package->cms()->locate($slug) as $dso) {
                    $opts[] = [$dso,$verb];
                }
                //break when a result is found, so that slugs that are a prefix
                //of the found slug don't produce 300 pages linking to
                //nonexistent verbs of parent pages
                if ($opts) {
                    break;
                }
            }
            //deal with whatever was found
            if (!$opts) {
                //no slugs found to match this url
            } elseif (count($opts) == 1) {
                //single result, just set noun to it
                list($noun, $verb) = array_pop($opts);
                $package->noun($noun);
                //make sure URL has the correct trailing slashes
                $package->url($package->noun()->url($verb, $url['args']));
            } else {
                //multiple results, produce a 300 page
                $package->error(300, 'Multiple options found');
                $package['response.300'] = [];
                foreach ($opts as $opt) {
                    $args = $url['args'];
                    unset($args['digraph_url']);
                    $package->push('response.300', [
                        'object' => $opt[0]['dso.id'],
                        'link' => $opt[0]->url($opt[1], $args)->html(null, true)->string()
                    ]);
                }
            }
            //redirect if parsed URL doesn't match original request
            //this is used for both ensuring that nouns (including slugs)
            //have trailing slashes, and that arguments are in alphabetical
            //order (which is important for caching)
            $url = $package->url()->string();
            $actual = $package->cms()->config['url.protocol'].$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            if ($url != $actual) {
                $package->log('URL mismatch');
                $package->log($url.' in package');
                $package->log($actual.' actual');
                $package->redirect($url);
            }
        }
    }

    protected function doConstruct($name)
    {
    }
}
