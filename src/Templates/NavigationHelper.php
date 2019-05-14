<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Templates;

use Digraph\Helpers\AbstractHelper;

class NavigationHelper extends AbstractHelper
{
    protected $parentOfCache = [];

    public function breadcrumb($url) : array
    {
        $bc = [];
        $bc["$url"] = $url;
        $bc = $this->bcBuilder($bc);
        return array_values(array_reverse($bc));
    }

    protected function bcBuilder($bc)
    {
        $parent = $this->parentOf(end($bc));
        if (!$parent) {
            return $bc;
        }
        if ($parent['noun'] == '__none' && $parent['verb'] == 'display') {
            return $bc;
        }
        if ($parent['noun'] == 'home' && $parent['verb'] == 'display') {
            $bc["$parent"] = $parent;
            return $bc;
        }
        if (isset($bc["$parent"])) {
            return $bc;
        }
        $bc["$parent"] = $parent;
        return $this->bcBuilder($bc);
    }

    protected function getConfiguredParent($url)
    {
        $vars = [
            'noun' => $url['noun'],
            'verb' => $url['verb'],
            'args' => $url->argString()
        ];
        foreach ($url['args'] as $key => $value) {
            $vars['arg_'.$key] = $value;
        }
        $nouns = $verbs = [];
        $nouns[] = $url['noun'];
        $verbs[] = $url['verb'];
        $type = 'common';
        if ($url['object'] && $object = $this->cms->read($url['object'])) {
            $type = 'proper';
            $nouns[] = $object['dso.id'];
            $nouns[] = $object['dso.type'];
            if ($parent = $object->parent()) {
                $vars['parent'] = $parent['dso.id'];
            }
        }
        $nouns[] = '*';
        $verbs[] = '*';
        $nouns = array_unique($nouns);
        $verbs = array_unique($verbs);
        foreach ($nouns as $noun) {
            foreach ($verbs as $verb) {
                if ($path = $this->cms->config["navigation.parents.$type.$noun/$verb"]) {
                    foreach ($vars as $key => $value) {
                        $path = str_replace('!'.$key, $value, $path);
                    }
                    return $this->cms->helper('urls')->parse($path);
                } elseif ($path === false) {
                    return null;
                }
            }
        }
        return null;
    }

    protected function parentOf($url)
    {
        if (!isset($this->parentOfCache["$url"])) {
            $parent = null;
            if ($parent = $this->getConfiguredParent($url)) {
                //do nothing, we found the parent in navigation.parents
            } elseif ($url['object'] && $object = $this->cms->read($url['object'])) {
                //ask object for parent
                $parent = $object->parentUrl($url['verb']);
            } else {
                $parent = $this->cms->helper('urls')->parse($this->cms->config['navigation.parents.fallback']);
            }
            $this->parentOfCache["$url"] = $parent;
        }
        return @$this->parentOfCache["$url"];
    }

    public function menu($conf) : array
    {
        $menu = [];
        if (!is_array($conf)) {
            $conf = $this->cms->config['navigation.menus.'.$conf];
        }
        if ($conf) {
            $urls = $this->cms->helper('urls');
            foreach ($conf as $url) {
                if (is_array($url)) {
                    //array entries afford a great deal of control
                    if ($p = $this->cms->read(@$url['children'])) {
                        foreach ($p->children() as $c) {
                            $menu[] = $c->url();
                        }
                    }
                } else {
                    //string entries just parse as internal URLs
                    $menu[] = $urls->parse($url);
                }
            }
        }
        return $menu;
    }
}
