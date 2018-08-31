<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Templates;

use Digraph\Helpers\AbstractHelper;

class NavigationHelper extends AbstractHelper
{
    public function menu($name) : array
    {
        $menu = [];
        $conf = $this->cms->config['navigation.menus.'.$name];
        if ($conf) {
            $urls = $this->cms->helper('urls');
            if (is_array($conf)) {
                foreach ($conf as $key => $value) {
                    $menu[] = $urls->parse($value);
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
