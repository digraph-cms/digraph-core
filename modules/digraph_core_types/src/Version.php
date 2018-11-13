<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

class Version extends Page
{
    const ROUTING_NOUNS = ['version'];

    public function effectiveDate()
    {
        if (!$this['digraph.published.force'] && $this['digraph.published.start']) {
            return $this['digraph.published.start'];
        }
        return $this['dso.created.date'];
    }
}
