<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Build;

use Digraph\Mungers\AbstractMunger;

class Locate extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        $url = $package->url();
        if ($noun = $package->cms()->factory()->read($url['noun'])) {
            $package->noun($noun);
        } else {
            if ($url->pathString() == '') {
                $slugs = [['home',null]];
            } else {
                $slugs = [[$url->pathString(),null]];
                if (strpos($url->pathString(), '/') !== false) {
                    $path = explode('/', $url->pathString());
                    $verb = array_pop($path);
                    $slugs[] = [implode('/', $path),$verb];
                }
            }
            //search for possible slug matches
            $opts = [];
            foreach ($slugs as $slug) {
                list($slug, $verb) = $slug;
                $search = $package->cms()->factory()->search();
                $search->where('${digraph.slug} = :slug');
                foreach ($search->execute([':slug'=>$slug]) as $dso) {
                    $opts[] = [$dso,$verb];
                }
            }
            //deal with whatever was found
            if (!$opts) {
                //no slugs found to match this url
                return;
            } elseif (count($opts) == 1) {
                //single result, just set noun to it
                list($noun, $verb) = array_pop($opts);
                $package->noun($noun);
                $package['response.canonicalurl'] = $package->noun()->url($verb, $url['args'], true)->string();
                //make sure URL has the correct trailing slashes
                $package->url($package->noun()->url($verb, $url['args']));
            } else {
                //multiple results, produce a 300 page
                $package->error(300, 'Multiple options found');
                $package['temp.300options'] = $opts;
            }
            //redirect if parsed URL doesn't match original request
            //this is used for both ensuring that nouns (including slugs)
            //have trailing slashes, and that arguments are in alphabetical
            //order (which is important for caching)
            if ($package->url()->routeString() != $package['request.url.original']) {
                $package['response.cacheable'] = false;
                $package->redirect($package->url()->string(), 301);
                return;
            }
        }
    }

    protected function doConstruct($name)
    {
    }
}
