<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Graph;

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
    edge_type TEXT NOT NULL,
	edge_weight INTEGER DEFAULT 0 NOT NULL
);
EOT;

    /* indexes to create on table */
    const IDX = [
        'CREATE INDEX IF NOT EXISTS digraph_edges_start_IDX ON digraph_edges (edge_start);',
        'CREATE INDEX IF NOT EXISTS digraph_edges_end_IDX ON digraph_edges (edge_end);',
        'CREATE INDEX IF NOT EXISTS digraph_edges_end_IDX ON digraph_edges (edge_type);',
        'CREATE UNIQUE INDEX IF NOT EXISTS digraph_edges_start_end_IDX ON digraph_edges (edge_start,edge_end,edge_type);'
    ];

    protected function toObjects($rows)
    {
        return array_filter(
            array_map(
                function ($e) {
                    if (is_array($e)) {
                        return new Edge($e, $this);
                    } else {
                        return false;
                    }
                },
                $rows
            )
        );
    }

    public function list(int $limit, int $offset) : array
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
            return $this->toObjects($s->fetchAll(\PDO::FETCH_ASSOC));
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
        foreach ($export['nouns'] as $noun) {
            foreach ($this->children($noun['dso.id']) as $edge) {
                $edges[] = $edge;
            }
            foreach ($this->parents($noun['dso.id']) as $edge) {
                $edges[] = $edge;
            }
        }
        $out = [];
        foreach ($edges as $e) {
            $out[$e->id()] = [
                'start' => $e->start(),
                'end' => $e->end(),
                'type' => $e->type()
            ];
        }
        return array_values($out);
    }

    public function hook_import($data, $nouns)
    {
        $log = [];
        foreach ($data['helper']['edges'] as $item) {
            $start = $item['start'];
            $end = $item['end'];
            $type = $item['type'];
            try {
                if ($this->create($start, $end, $type)) {
                    $log[] = "$start =&gt; $end ($type)";
                }
            }catch (\Exception $e) {
                $log[] = "Exception: ".serialize($item);
            }
        }
        return $log;
    }

    public function get(string $start, string $end, $type=null)
    {
        $args = [':start'=>$start,':end'=>$end];
        if (!$type) {
            $s = $this->pdo->prepare(
                'SELECT * FROM digraph_edges WHERE edge_start = :start AND edge_end = :end LIMIT 1'
            );
        } else {
            $args[':type'] = $type;
            $s = $this->pdo->prepare(
                'SELECT * FROM digraph_edges WHERE edge_start = :start AND edge_end = :end AND edge_type = :type LIMIT 1'
            );
        }
        if ($s->execute($args)) {
            if ($r = $s->fetch(\PDO::FETCH_ASSOC)) {
                return new Edge($r);
            } else {
                return null;
            }
        }
        return null;
    }

    public function children(string $start, $type=null, $ids=false)
    {
        $r = [];
        $args = [':start'=>$start];
        if (!$type) {
            $s = $this->pdo->prepare(
                'SELECT * FROM digraph_edges WHERE edge_start <> \'{ROOT}\' AND edge_start = :start ORDER BY edge_weight desc, edge_id asc'
            );
        } else {
            $args[':type'] = $type;
            $s = $this->pdo->prepare(
                'SELECT * FROM digraph_edges WHERE edge_start <> \'{ROOT}\' AND edge_start = :start AND edge_type = :type ORDER BY edge_weight desc, edge_id asc'
            );
        }
        //execute
        if ($s->execute($args)) {
            if ($ids) {
                return array_map(
                    function ($e) {
                        return $e['edge_end'];
                    },
                    $s->fetchAll(\PDO::FETCH_ASSOC)
                );
            } else {
                return $this->toObjects($s->fetchAll(\PDO::FETCH_ASSOC));
            }
        } else {
            return [];
        }
    }

    public function parents(string $end, $type=null, $ids=false)
    {
        $r = [];
        $args = [':end'=>$end];
        if (!$type) {
            $s = $this->pdo->prepare(
                'SELECT * FROM digraph_edges WHERE edge_start <> \'{ROOT}\' AND edge_end = :end ORDER BY edge_weight desc, edge_id asc'
            );
        } else {
            $args[':type'] = $type;
            $s = $this->pdo->prepare(
                'SELECT * FROM digraph_edges WHERE edge_start <> \'{ROOT}\' AND edge_end = :end AND edge_type = :type ORDER BY edge_weight desc, edge_id asc'
            );
        }
        //execute
        if ($s->execute($args)) {
            if ($ids) {
                return array_map(
                    function ($e) {
                        return $e['edge_start'];
                    },
                    $s->fetchAll(\PDO::FETCH_ASSOC)
                );
            } else {
                return $this->toObjects($s->fetchAll(\PDO::FETCH_ASSOC));
            }
        } else {
            return [];
        }
    }

    protected function autoType($start, $end)
    {
        $type = 'normal';
        $start = $this->cms->read($start);
        $end = $this->cms->read($end);
        if ($start && method_exists($start, 'childEdgeType')) {
            $type = $start->childEdgeType($end);
        }
        if ($end && method_exists($end, 'parentEdgeType')) {
            $type = $end->parentEdgeType($start);
        }
        if ($type === null) {
            $type = 'normal';
        }
        return $type;
    }

    /**
     * create a new edge, or alter the weight of an existing edge
     */
    public function create(string $start, string $end, $type=null, $weight=0)
    {
        if (!$type) {
            $type = $this->autoType($start, $end);
        }
        if ($e = $this->get($start, $end, $type)) {
            if ($e->weight() == $weight) {
                //edge exists with same weight
                return true;
            } else {
                //edge exists with different weight
                $s = $this->pdo->prepare(
                    'UPDATE digraph_edges SET edge_weight = :weight WHERE edge_start = :start AND edge_end = :end AND edge_type = :type'
                );
                if ($start !== '{ROOT}') {
                    $this->updateRootTracking($end);
                }
                return $s->execute([':start'=>$start,':end'=>$end,':type'=>$type,':weight'=>$weight]);
            }
        }
        //need to make a new edge
        $s = $this->pdo->prepare(
            'INSERT INTO digraph_edges (edge_start,edge_end,edge_type,edge_weight) VALUES (:start,:end,:type,:weight)'
        );
        $out = $s->execute([':start'=>$start,':end'=>$end,':type'=>$type,':weight'=>$weight]);
        if ($start !== '{ROOT}') {
            $this->updateRootTracking($end);
        }
        return $out;
    }

    public function delete(string $start, string $end, $type=null)
    {
        $args = [':start'=>$start,':end'=>$end];
        if (!$type) {
            $s = $this->pdo->prepare(
                'DELETE FROM digraph_edges WHERE edge_start = :start AND edge_end = :end'
            );
        } else {
            $args[':type'] = $type;
            $s = $this->pdo->prepare(
                'DELETE FROM digraph_edges WHERE edge_start = :start AND edge_end = :end AND edge_type = :type'
            );
        }
        $out = $s->execute($args);
        if ($start !== '{ROOT}') {
            $this->updateRootTracking($end);
        }
        return $out;
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

    public function roots($ids=false)
    {
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_edges WHERE edge_start = \'{ROOT}\' ORDER BY edge_weight desc, edge_id asc'
        );
        //execute
        if ($s->execute($args)) {
            if ($ids) {
                return array_map(
                    function ($e) {
                        return $e['edge_end'];
                    },
                    $s->fetchAll(\PDO::FETCH_ASSOC)
                );
            } else {
                return $this->toObjects($s->fetchAll(\PDO::FETCH_ASSOC));
            }
        } else {
            return [];
        }
    }

    public function updateRootTracking($id)
    {
        if (!$this->parents($id)) {
            if ($this->cms->read($id)) {
                $this->create('{ROOT}', $id, 'root');
            }
        } else {
            $this->delete('{ROOT}', $id, 'root');
        }
    }
}
