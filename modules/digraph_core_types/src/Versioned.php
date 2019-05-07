<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

use Digraph\DSO\Noun;

class Versioned extends Noun
{
    const PUBLISH_CONTROL = false;
    const ROUTING_NOUNS = ['versioned'];
    const VERSION_TYPE = 'version';
    const SLUG_ENABLED = true;

    public function excludedChildTypes()
    {
        return [static::VERSION_TYPE];
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
            return;
        }
        if (!$version->isPublished()) {
            $this->factory->cms()->helper('notifications')->warning(
                $this->factory->cms()->helper('strings')->string(
                    'notifications.unpublished',
                    ['name'=>$version->name()]
                )
            );
        }
        return $this->factory->cms()->helper('filters')->filterContentField($version['digraph.body'], $this['dso.id']);
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
                ->children($this['dso.id'], 1, [static::VERSION_TYPE])
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
