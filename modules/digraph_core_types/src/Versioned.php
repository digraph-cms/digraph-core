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
        $search = $this->factory->search();
        $search->where('${digraph.parents_string} LIKE :pattern AND ${dso.type} = :versiontype');
        return $this->sortVersions($search->execute([
            ':pattern' => '%|'.$this['dso.id'].'|%',
            ':versiontype' => static::VERSION_TYPE
        ]));
    }

    public function currentVersion()
    {
        //search for the most recent with a publication start date
        $search = $this->factory->search();
        $search->where('${digraph.parents_string} LIKE :pattern AND ${dso.type} = :versiontype AND ${digraph.published.force} is null AND ${digraph.published.start} is not null');
        $search->order('${digraph.published.start} desc');
        $r = $search->execute([
            ':pattern' => '%|'.$this['dso.id'].'|%',
            ':versiontype' => static::VERSION_TYPE
        ]);
        //search for the most recent creation date
        $search = $this->factory->search();
        $search->where('${digraph.parents_string} LIKE :pattern AND ${dso.type} = :versiontype');
        $search->order('${digraph.dso.created.date} desc');
        $r = $r+$search->execute([
            ':pattern' => '%|'.$this['dso.id'].'|%',
            ':versiontype' => static::VERSION_TYPE
        ]);
        //sort the results
        $r = $this->sortVersions($r);
        //return the most recent
        if ($r) {
            return reset($r);
        }
        return null;
    }

    public function formMap(string $actions) : array
    {
        $map = parent::formMap($actions);
        $map['001_digraph_title'] = false;
        $map['500_digraph_body'] = false;
        return $map;
    }
}
