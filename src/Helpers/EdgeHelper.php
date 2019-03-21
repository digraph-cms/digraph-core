<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\CMS;

/**
 * EdgeHelper is used to quickly manage the edges between Nouns. It operates
 * entirely using the string representations of dso IDs, so sorting and actually
 * querying the content table will need to be done elsewhere. This is actually
 * a feature and not a bug, because it reduces complexity here and allows other
 * classes more control over sorting and filtering.
 */
class EdgeHelper extends \Digraph\Helpers\AbstractHelper
{
    /* DDL for table */
    const DDL = <<<EOT
CREATE TABLE IF NOT EXISTS digraph_edges (
	edge_start TEXT NOT NULL,
	edge_end TEXT NOT NULL,
	edge_weight INTEGER DEFAULT 0 NOT NULL
);
EOT;

    /* indexes to create on table */
    const IDX = [
        'CREATE INDEX IF NOT EXISTS digraph_edges_start_IDX ON digraph_edges (edge_start);',
        'CREATE INDEX IF NOT EXISTS digraph_edges_end_IDX ON digraph_edges (edge_end);',
        'CREATE UNIQUE INDEX IF NOT EXISTS digraph_edges_start_end_IDX ON digraph_edges (edge_start,edge_end);'
    ];

    public function __construct(CMS &$cms)
    {
        parent::__construct($cms);
        $this->pdo = $this->cms->pdo();
        //ensure that table and indexes exist
        $this->pdo->exec(static::DDL);
        foreach (static::IDX as $idx) {
            $this->pdo->exec($idx);
        }
    }

    public function get(string $start, string $end)
    {
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_edges WHERE edge_start = :start AND edge_end = :end LIMIT 1'
        );
        if ($s->execute([':start'=>$start,':end'=>$end])) {
            return $s->fetch(\PDO::FETCH_ASSOC);
        }
        return null;
    }

    public function children(string $start)
    {
        $r = [];
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_edges WHERE edge_start = :start ORDER BY edge_weight desc'
        );
        //execute
        if ($s->execute([':start'=>$start])) {
            foreach ($s->fetchAll(\PDO::FETCH_ASSOC) as $e) {
                $r[] = $e['edge_end'];
            }
        }
        //return
        return $r;
    }

    public function parents(string $end)
    {
        $r = [];
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_edges WHERE edge_end = :end ORDER BY edge_weight desc'
        );
        //execute
        if ($s->execute([':end'=>$end])) {
            foreach ($s->fetchAll(\PDO::FETCH_ASSOC) as $e) {
                $r[] = $e['edge_start'];
            }
        }
        //return
        return $r;
    }

    /**
     * create a new edge, or alter the weight of an existing edge
     */
    public function create(string $start, string $end, $weight=0)
    {
        if ($e = $this->get($start, $end)) {
            if ($e['edge_weight'] == $weight) {
                //edge exists with same weight
                return true;
            } else {
                //edge exists with different weight
                $s = $this->pdo->prepare(
                    'UPDATE digraph_edges SET edge_weight = :weight WHERE edge_start = :start AND edge_end = :end'
                );
                return $s->execute([':start'=>$start,':end'=>$end,':weight'=>$weight]);
            }
        }
        //need to make a new edge
        $s = $this->pdo->prepare(
            'INSERT INTO digraph_edges (edge_start,edge_end,edge_weight) VALUES (:start,:end,:weight)'
        );
        if ($s->execute([':start'=>$start,':end'=>$end,':weight'=>$weight])) {
            //remove digraph.noparent from end noun
            if (($en = $this->cms->read($end, false)) && $en['digraph.noparent']) {
                $en['digraph.noparent'] = false;
                $en->update(true);
            }
            return true;
        }
        return false;
    }

    public function remove(string $start, string $end)
    {
        //need to make a new edge
        $s = $this->pdo->prepare(
            'DELETE FROM digraph_edges WHERE edge_start = :start AND edge_end = :end'
        );
        if ($s->execute([':start'=>$start,':end'=>$end])) {
            //add digraph.noparent to end noun if this was its last parent
            if (!$this->parents($end)) {
                if ($en = $this->cms->read($end, false) && !$en['digraph.noparent']) {
                    $en['digraph.noparent'] = true;
                    $en->update(true);
                }
            }
            return true;
        }
    }

    public function removeAll(string $id)
    {
        //need to make a new edge
        $s = $this->pdo->prepare(
            'DELETE FROM digraph_edges WHERE edge_start = :id OR edge_end = :id'
        );
        if ($s->execute([':id'=>$id])) {
            //add digraph.noparent to noun if we've removed its last parent
            if (!$this->parents($id)) {
                if ($en = $this->cms->read($end, false) && !$en['digraph.noparent']) {
                    $en['digraph.noparent'] = true;
                    $en->update(true);
                }
            }
            return true;
        }
    }
}
