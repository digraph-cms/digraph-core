<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;

class CacheHelper extends AbstractHelper
{
    public function hook_cron()
    {
        $pruned = [];
        $errors = [];
        foreach ($this->cms->allCaches() as $name) {
            $cache = $this->cms->cache($name);
            if (method_exists($cache, 'prune')) {
                if ($r = $cache->prune()) {
                    $pruned[] = $name;
                } else {
                    $errors[] = 'error pruning '.$name;
                }
            }
        }
        return [
            'result' => count($pruned),
            'errors' => $errors,
            'names' => $pruned
        ];
    }
}
