<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Urls;

use Digraph\Helpers\AbstractHelper;

class UrlHelper extends AbstractHelper
{
    public function parse(string $input) : ?Url
    {
        $url = new Url();
        //break the URL into its parts
        list($path, $args) = explode(Url::ARGINITIALSEPARATOR, $input, 2);
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
