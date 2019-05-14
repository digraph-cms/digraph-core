<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

use Digraph\DSO\Noun;

class Versioned extends Noun
{
    const ROUTING_NOUNS = ['versioned'];
    const VERSION_TYPE = 'version';
    const SLUG_ENABLED = true;

    public function childEdgeType($child)
    {
        if ($child instanceof Version) {
            return 'version';
        } else {
            return null;
        }
    }

    public function title($verb = null)
    {
        if (!($version = $this->currentVersion())) {
            return parent::title($verb);
        }
        return $version->title($verb);
    }

    public function body()
    {
        if (!($version = $this->currentVersion())) {
            return '[no version of this page is available]';
        }
        return $version->body();
    }

    public function actions($links)
    {
        $links['version_list'] = '!id/versions';
        if ($c = $this->currentVersion()) {
            $links['edit_currentversion'] = $c['dso.id'].'/edit';
        }
        $links['add_revision'] = '!id/add?type='.static::VERSION_TYPE;
        return $links;
    }

    protected function sortVersions($versions)
    {
        $sorted = [];
        foreach ($versions as $v) {
            $sorted[$v->effectiveDate().'-'.$v['dso.id']] = $v;
        }
        ksort($sorted);
        return array_reverse($sorted);
    }

    public function availableVersions()
    {
        return $this->sortVersions(
            $this->factory->cms()
                ->helper('graph')
                ->children($this['dso.id'], 'version', 1)
        );
    }

    public function currentVersion()
    {
        $vs = $this->availableVersions();
        return $vs?array_shift($vs):null;
    }

    public function formMap(string $actions) : array
    {
        $map = parent::formMap($actions);
        $map['digraph_title'] = false;
        $map['digraph_body'] = false;
        return $map;
    }
}
