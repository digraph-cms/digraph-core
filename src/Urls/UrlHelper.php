<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Urls;

use Digraph\DSO\Noun;
use Digraph\Helpers\AbstractHelper;

class UrlHelper extends AbstractHelper
{
    public function construct()
    {
        Url::$helper = $this;
    }

    public function noun($url)
    {
        if ($url->noun()) {
            return $url->noun();
        }
        if ($url['object']) {
            return $this->cms->read($url['object']);
        }
    }

    public function data(Url &$url, $data)
    {
        $url->setData($data);
        $this->hash($url);
    }

    public function hash(Url &$url)
    {
        unset($url['args.__hash']);
        $url['args.__hash'] = $this->hashUrl($url);
    }

    public function checkHash(Url $url): ?bool
    {
        // if url doesn't have a hash return null
        if (!$url['args.__hash']) {
            return null;
        }
        // otherwise return bool
        $hash = $url['args.__hash'];
        $url = clone $url;
        unset($url['args.__hash']);
        return $hash == $this->hashUrl($url);
    }

    protected function hashUrl(Url $url): string
    {
        if (!$this->cms->config['secret']) {
            throw new \Exception("You must set a value for config[\"secret\"] to use hash-protected URLs");
        }
        return hash('sha256', $url['args.__data'] . $this->cms->config['secret']);
    }

    public function parse(string $input): ?Url
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
                $argarr[$key] = $value ? urldecode($value) : false;
            }
            $url['args'] = $argarr;
        }
        //look up slug
        //create list of possible noun/verb combinations
        $slugs = [];
        if ($url->pathString() == '') {
            $slugs = [['home', null]];
        } else {
            $slugs = [[$url->pathString(), null]];
            if (strpos($url->pathString(), '/') !== false) {
                $path = explode('/', $url->pathString());
                $verb = array_pop($path);
                $slugs[] = [implode('/', $path), $verb];
            }
        }
        //search for possible slug matches
        foreach ($slugs as $slug) {
            if ($dso = $this->cms->read($slug[0])) {
                $url['object'] = $dso['dso.id'];
                if ($url['noun'] == $dso['dso.id']) {
                    $url->canonical(true);
                } else {
                    $url->canonical(false);
                }
                $url->noun($dso);
                return $this->addText($url);
            }
        }
        //check if alias exists
        $url['base'] = '';
        if ($alias = $this->cms->config['urls.aliases.' . $url]) {
            return $this->parse($alias);
        }
        //check hash, remove data/hash if invalid
        $this->removeInvalidData($url);
        //return
        $url['base'] = $this->cms->config['url.base'];
        return $this->addText($url);
    }

    public function removeInvalidData(Url &$url)
    {
        if ($url['args.__data']) {
            if (!$this->checkHash($url)) {
                unset($url['args.__data']);
                unset($url['args.__hash']);
            }
        }
    }

    public function addText($url, Noun $object = null)
    {
        $vars = [
            'noun' => $url['noun'],
            'verb' => $url['verb'],
            'name' => $url['noun'],
        ];
        foreach ($url['args'] as $key => $value) {
            $vars['arg_' . $key] = $value;
        }
        if ($object = $object ?? $url->noun() ?? $this->noun($url)) {
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
                if ($text = $this->cms->config["strings.urls.$type.$noun/$verb"]) {
                    foreach ($vars as $key => $value) {
                        $text = str_replace('!' . $key, $value, $text);
                    }
                    $url['text'] = $text;
                    return $url;
                }
            }
        }
        return $url;
    }

    public function url($noun = null, $verb = null, $args = null)
    {
        $url = new Url();
        $url['base'] = $this->cms->config['url.base'];
        $url['noun'] = $noun;
        $url['verb'] = $verb;
        $url['args'] = $args;
        //check hash, remove data/hash if invalid
        $this->removeInvalidData($url);
        return $url;
    }
}
