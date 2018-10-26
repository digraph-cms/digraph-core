<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

use Digraph\DSO\Noun;

class Versioned extends Noun
{
    const VERSION_TYPE = 'version';

    protected function sortVersions($versions)
    {
        $sorted = [];
        foreach ($versions as $v) {
            if (!$v['digraph.published.force'] && $v['digraph.published.start']) {
                $sorted[$v['digraph.published.start'].'-'.$v['dso.id']] = $v;
            } else {
                $sorted[$v['dso.created.date'].'-'.$v['dso.id']] = $v;
            }
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
}
