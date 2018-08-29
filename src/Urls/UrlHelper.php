<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Urls;

use Digraph\Helpers\AbstractHelper;

class UrlHelper extends AbstractHelper
{
    public function dso($url)
    {
        if ($url['dso']) {
            return $this->cms->factory()->read($url['dso']);
        }
    }

    public function parse(string $input, bool $fast = false) : ?Url
    {
        $url = new Url();
        //break the URL into its parts
        @list($path, $args) = explode(Url::ARGINITIALSEPARATOR, $input, 2);
        $path = preg_replace('/\/$/', '', $path);
        if (!strpos($path, '/')) {
            $path .= '/';
        }
        list($verb, $noun) = explode(Url::VERBSEPARATOR, strrev($path), 2);
        $noun = strrev($noun);
        $verb = strrev($verb);
        if ($noun) {
            $url['noun'] = $noun;
        }
        if ($verb) {
            $url['verb'] = $verb;
        }
        //turn args into an array
        if ($args) {
            $argarr = array();
            $args = explode(Url::ARGSEPARATOR, $args);
            foreach ($args as $part) {
                list($key, $value) = explode(Url::ARGVALUESEPARATOR, $part, 2);
                $argarr[$key] = $value?$value:true;
            }
            $url['args'] = $argarr;
        }
        //if fast wasn't requested, look up slug
        if (!$fast) {
            //create list of possible noun/verb combinations
            $slugs = [];
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
            foreach ($slugs as $slug) {
                list($slug, $verb) = $slug;
                $search = $this->cms->factory()->search();
                $search->where('${digraph.slug} = :slug');
                foreach ($search->execute([':slug'=>$slug]) as $dso) {
                    $url = $dso->url($verb);
                    return $url;
                }
            }
            //if no DSO could be found, return null
            return null;
        }
        //return
        $url['base'] = $this->cms->config['url.base'];
        return $url;
    }

    public function url($noun=null, $verb=null, $args=null)
    {
        $url = new Url();
        $url['base'] = $this->cms->config['url.base'];
        $url['noun'] = $noun;
        $url['verb'] = $verb;
        $url['args'] = $args;
        return $url;
    }
}
