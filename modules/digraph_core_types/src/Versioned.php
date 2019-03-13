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
            $this->factory->cms()->helper('notifications')->warning(
                $this->factory->cms()->helper('strings')->string('versioned.no_versions')
            );
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
        /* pull list of child IDs from edge helper, create IN clause to get them */
        $cids = $this->cms()->helper('edges')->children($this['dso.id']);
        if (!$cids) {
            //short-circuit if edge helper has no children for this noun
            return [];
        }
        $cids = '${dso.id} in (\''.implode('\',\'', $cids).'\') AND ';
        /* run search */
        $search = $this->factory->search();
        $search->where($cids.'${dso.type} = :versiontype');
        return $this->sortVersions($search->execute([
            ':versiontype' => static::VERSION_TYPE
        ]));
    }

    public function currentVersion()
    {
        $vs = $this->availableVersions();
        return $vs?array_shift($vs):null;
    }

    public function formMap(string $actions) : array
    {
        $map = parent::formMap($actions);
        $map['001_digraph_title'] = false;
        $map['500_digraph_body'] = false;
        return $map;
    }
}
