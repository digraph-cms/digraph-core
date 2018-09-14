<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Urls;

use Digraph\Helpers\AbstractHelper;

class UrlHelper extends AbstractHelper
{
    public function noun($url)
    {
        if ($url['object']) {
            return $this->cms->read($url['object']);
        }
    }

    public function parse(string $input) : ?Url
    {
        $url = $this->url();
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
        //look up slug
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
            if ($dso = $this->cms->read($slug[0])) {
                $url['object'] = $url['canonicalurl'] = $dso['dso.id'];
                return $this->addText($url);
            }
        }
        //check if alias exists
        $url['base'] = '';
        if ($alias = $this->cms->config['urls.aliases.'.$url]) {
            return $this->parse($alias);
        }
        //return
        $url['base'] = $this->cms->config['url.base'];
        return $this->addText($url);
    }

    public function addText($url)
    {
        $vars = [
            'noun' => $url['noun'],
            'verb' => $url['verb'],
            'name' => $url['noun']
        ];
        foreach ($url['args'] as $key => $value) {
            $vars['arg_'.$key] = $value;
        }
        if ($object = $this->noun($url)) {
            $vars['type'] = $object['dso.type'];
            $vars['name'] = $object->name();
            $vars['title'] = $object->title();
        }
        $nouns = $verbs = [];
        $nouns[] = $url['noun'];
        $verbs[] = $url['verb'];
        $type = 'common';
        if ($object) {
            $type = 'proper';
            $nouns[] = $object['dso.id'];
            $nouns[] = $object['dso.type'];
        }
        $nouns[] = '*';
        $verbs[] = '*';
        $nouns = array_unique($nouns);
        $verbs = array_unique($verbs);
        foreach ($nouns as $noun) {
            foreach ($verbs as $verb) {
                if ($text = $this->cms->config["urls.names.$type.$noun/$verb"]) {
                    foreach ($vars as $key => $value) {
                        $text = str_replace('?'.$key, $value, $text);
                    }
                    $url['text'] = $text;
                    return $url;
                }
            }
        }
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
