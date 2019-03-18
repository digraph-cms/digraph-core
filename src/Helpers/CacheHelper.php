<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;

class CacheHelper extends AbstractHelper
{
    public function hook_cron()
    {
        $return = [];
        foreach ($this->cms->allCaches() as $name) {
            $cache = $this->cms->cache($name);
            if (method_exists($cache, 'prune')) {
                $return[$name] = $cache->prune();
            }
        }
        return $return;
    }
}
