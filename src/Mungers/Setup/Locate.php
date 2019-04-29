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
            //we're using a canonical url, which always takes priority over any
            //and all other interpretations
            $package->noun($noun);
            $package['located-options'] = [[
                $url['noun'],
                $url['verb'],
                $noun['dso.id']
            ]];
        } else {
            //we appear to not be using a canonical url
            //start by building a list of possible slug/verb combinations
            if ($url->pathString() == '') {
                $slugs = [['home',null]];
            } else {
                $slugs = [];
                $path = explode('/', trim($url->pathString(), "/ \t\n\r\0\x0B"));
                $noun = [];
                $slugs[] = ['home',implode('/', $path)];
                while ($path) {
                    $noun[] = array_shift($path);
                    $slugs[] = [implode('/', $noun),implode('/', $path)];
                }
                $slugs = array_reverse($slugs);
            }
            //search for possible slug matches
            $opts = [];
            foreach ($slugs as $slug) {
                list($slug, $verb) = $slug;
                foreach ($package->cms()->locate($slug) as $dso) {
                    $opts[] = [$slug,$verb,$dso];
                }
            }
            //filter by whether noun/verb pair is viable
            //verb must either be alphanumeric-ish, or accepted by verbHandler()
            $opts = array_filter($opts, function ($e) {
                $canHandle = method_exists($e[2], 'verbExists') && $e[2]->verbExists($e[1]);
                return $canHandle || preg_match('/^[a-z0-9\-_\.]*$/', $e[1]);
            });
            //throw 404 if no possible options were found
            if (!$opts) {
                $package['located-options'] = [];
            } else {
                $package['located-options'] = array_map(
                    function ($e) {
                        return [
                            $e[0],
                            $e[1]?$e[1]:'display',
                            $e[2]['dso.id']
                        ];
                    },
                    $opts
                );
            }
            // //deal with whatever was found
            // if (!$opts) {
            //     //no slugs found to match this url
            // } elseif (count($opts) == 1) {
            //     //single result, just set noun to it
            //     list($noun, $verb) = array_pop($opts);
            //     $package->noun($noun);
            //     //make sure URL has the correct trailing slashes
            //     $package->url($package->noun()->url($verb, $url['args']));
            // } else {
            //     //multiple results, produce a 300 page
            //     $package->error(300, 'Multiple options found');
            //     $package['response.300'] = [];
            //     foreach ($opts as $opt) {
            //         $args = $url['args'];
            //         unset($args['digraph_url']);
            //         $package->push('response.300', [
            //             'object' => $opt[0]['dso.id'],
            //             'link' => $opt[0]->url($opt[1], $args)->html(null, true)->string()
            //         ]);
            //     }
            // }
            // //redirect if parsed URL doesn't match original request
            // //this is used for both ensuring that nouns (including slugs)
            // //have trailing slashes, and that arguments are in alphabetical
            // //order (which is important for caching)
            // $url = $package->url()->string();
            // $actual = $package->cms()->config['url.protocol'].$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            // if ($url != $actual) {
            //     $package->redirect($url);
            // }
        }
    }

    protected function doConstruct($name)
    {
    }
}
