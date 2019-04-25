<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\DSO\Noun;

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
    edge_id INTEGER PRIMARY KEY,
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

    public function list(int $limit, int $offset)
    {
        $args = [];
        $l = '';
        if ($limit) {
            $l .= ' LIMIT :limit';
            $args[':limit'] = $limit;
        }
        if ($offset) {
            $l .= ' OFFSET :offset';
            $args[':offset'] = $offset;
        }
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_edges ORDER BY edge_id desc'.$l
        );
        if ($s->execute($args)) {
            return $s->fetchAll(\PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function count()
    {
        $s = $this->pdo->prepare(
            'SELECT COUNT(edge_id) FROM digraph_edges'
        );
        if ($s->execute()) {
            $out = $s->fetchAll(\PDO::FETCH_ASSOC);
            return intval($out[0]['COUNT(edge_id)']);
        }
        return 0;
    }

    public function construct()
    {
        $this->pdo = $this->cms->pdo();
        //ensure that table and indexes exist
        $this->pdo->exec(static::DDL);
        foreach (static::IDX as $idx) {
            $this->pdo->exec($idx);
        }
        //set up hooks to delete edges
        $this->cms->helper('hooks')->noun_register('delete_permanent', [$this,'deleteAll']);
    }

    public function hook_export(&$export)
    {
        $edges = [];
        foreach ($export['noun_ids'] as $noun) {
            foreach ($this->children($noun) as $edge) {
                $edges[] = [$noun,$edge];
            }
            foreach ($this->parents($noun) as $edge) {
                $edges[] = [$edge,$noun];
            }
        }
        $out = [];
        foreach ($edges as $e) {
            if (!isset($out[$e])) {
                $out[$e] = [];
            }
            if (!in_array($e[1], $out[$e[0]])) {
                $out[$e[0]][] = $e[1];
            }
        }
        return $out;
    }

    public function hook_import($data, $nouns)
    {
        $log = [];
        foreach ($data['helper']['edges'] as $start => $ends) {
            foreach ($ends as $end) {
                if ($this->create($start, $end)) {
                    $log[] = "$start =&gt; $end";
                }
            }
        }
        return $log;
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

    public function children_recursive(string $noun, $depth=-1)
    {
        $children = $this->children($noun);
        if ($depth != 0) {
            $depth--;
            foreach ($children as $child) {
                foreach ($this->children_recursive($child, $depth) as $c) {
                    $children[] = $c;
                }
            }
        }
        return array_unique($children);
    }

    public function children(string $start)
    {
        $r = [];
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_edges WHERE edge_start = :start ORDER BY edge_weight desc, edge_id asc'
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
            'SELECT * FROM digraph_edges WHERE edge_end = :end ORDER BY edge_weight desc, edge_id asc'
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

    public function delete(string $start, string $end)
    {
        //need to make a new edge
        $s = $this->pdo->prepare(
            'DELETE FROM digraph_edges WHERE edge_start = :start AND edge_end = :end'
        );
        if ($s->execute([':start'=>$start,':end'=>$end])) {
            //add digraph.noparent to end noun if this was its last parent
            if (!$this->parents($end)) {
                if ($en = $this->cms->read($end, false)) {
                    $en['digraph.noparent'] = true;
                    $en->update(true);
                }
            } else {
                if ($en = $this->cms->read($end, false)) {
                    $en['digraph.noparent'] = false;
                    $en->update(true);
                }
            }
            return true;
        }
    }

    public function deleteAll($id)
    {
        if ($id instanceof Noun) {
            $id = $id['dso.id'];
        }
        //delete all edges related to this ID
        $s = $this->pdo->prepare(
            'DELETE FROM digraph_edges WHERE edge_start = :id OR edge_end = :id'
        );
        return $s->execute([':id'=>$id]);
    }
}
