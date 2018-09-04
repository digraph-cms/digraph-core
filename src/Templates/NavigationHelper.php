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
        return array_reverse($bc);
    }

    protected function bcBuilder($bc)
    {
        $parent = $this->parentOf(end($bc));
        if (!$parent) {
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

    protected function parentOf($url)
    {
        if (!isset($this->parentOfCache["$url"])) {
            $parent = null;
            if ($url['object'] && $object = $this->cms->read($url['object'])) {
                $parent = $object->parentUrl($url['verb']);
            }
            $this->parentOfCache["$url"] = $parent;
        }
        return @$this->parentOfCache["$url"];
    }

    public function menu($name) : array
    {
        $menu = [];
        $conf = $this->cms->config['navigation.menus.'.$name];
        if ($conf) {
            $urls = $this->cms->helper('urls');
            if (is_array($conf)) {
                foreach ($conf as $url) {
                    $menu[] = $urls->parse($url);
                }
            } else {
                $menu[] = $root = $urls->parse($conf);
                $rootDSO = $this->cms->read($root['object']);
                foreach ($rootDSO->children() as $child) {
                    $menu[] = $child->url();
                }
            }
        }
        return $menu;
    }
}
