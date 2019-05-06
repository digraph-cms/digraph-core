<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\DSO\Noun;

/**
 * GraphHelper abstracts basic graph operations, including searching,
 * traversing, and iterating through nouns. The idea is to put those sorts of
 * operations in one place where they can be optimized and refined.
 *
 * To that end, this helper isn't going to be very DRY. Every method is
 * designed for maximum ease-of-understanding, and to be as self-contained as
 * possible for future optimization and bug-fixing.
 *
 * This helper should be well-commented and documented, as it is intended as
 * the primary interface for dealing with the graph, with the possibility of
 * even eventually deprecating most other public interfaces.
 *
 * When traversing the graph, results should always be returned in the order
 * that would be created by a breadth-first search.
 */
class GraphHelper extends \Digraph\Helpers\AbstractHelper
{
    public function children($id, $depth=1, $onlyTypes=[], $omitTypes=[])
    {
        return $this->sqlResults($this->childIDs($id, $depth), $onlyTypes, $omitTypes);
    }

    public function parents($id, $depth=1, $onlyTypes=[], $omitTypes=[])
    {
        return $this->sqlResults($this->parentIDs($id, $depth), $onlyTypes, $omitTypes);
    }

    public function childIDs($id, $depth=1)
    {
        //return edge helper children method results if depth is 1, it's fastest
        if ($depth === 1) {
            return $this->cms->helper('edges')->children($id);
        }
        //otherwise build with traverse
        return $this->traverse($id, null, $depth);
    }

    public function parentIDs($id, $depth=1)
    {
        //return edge helper parent method results if depth is 1, it's fastest
        if ($depth === 1) {
            return $this->cms->helper('edges')->parents($id);
        }
        //otherwise build with traverse
        return $this->traverse($id, null, $depth, true);
    }

    public function route($start, $end, $reverse=false)
    {
        return $this->sqlResults($this->routeIDs($start, $end, $reverse));
    }

    public function routeIDs($start, $end, $reverse=false) : ?array
    {
        /*
        It's noteworthy that this method works in a slightly unintuitive way.
        It actually calls traverse in such a way that its searches always
        run backwards up the tree of possible routes. This is because most
        of the time nouns will have more children than parents, so going
        backwards will yield a much smaller tree.
         */
        if ($reverse) {
            list($start, $end) = [$end,$start];
        }
        //traverse to build a list of edges tracing backwards from end
        $edges = [];
        $this->traverse(
            $end,
            function ($id, $depth, $last) use (&$edges) {
                $edges[$id] = $last;
            },
            -1,
            true
        );
        //if start ID exists in the list of edges, there must be a route
        if (isset($edges[$start])) {
            $route = [$start];
            $id = $start;
            while (isset($edges[$id])) {
                $route[] = $edges[$id];
                $id = $edges[$id];
            }
            return $route;
        }
        //otherwise return null
        return null;
    }

    protected function sqlResults($ids=[], $onlyTypes=[], $omitTypes=[])
    {
        if ($s = $this->sqlSearch($ids, $onlyTypes, $omitTypes)) {
            $ids = array_flip($ids);
            foreach ($s->execute() as $n) {
                $ids[$n['dso.id']] = $n;
            }
            $ids = array_filter(
                $ids,
                function ($e) {
                    return $e instanceof Noun;
                }
            );
            return $ids;
        } else {
            return [];
        }
    }

    /**
     * Creates a Destructr search object set up to return a given list of IDs,
     * optionally limiting to and omitting by type
     */
    protected function sqlSearch($ids=[], $onlyTypes=[], $omitTypes=[])
    {
        //sanitize IDs
        $ids = $this->regexFilter($ids, '/^[a-z0-9]+$/');
        //sanitize type args
        $onlyTypes = $this->regexFilter($onlyTypes, '/^[a-z0-9\-]+$/');
        $omitTypes = $this->regexFilter($omitTypes, '/^[a-z0-9\-]+$/');
        //short circuit if none were valid
        if (!$ids) {
            return null;
        }
        //use DSO search
        $search = $this->cms->factory()->search();
        $where = [];
        //set up basic search for valid IDs
        $where[] = '${dso.id} in (\''.implode("','", $ids).'\')';
        //add clause to limit types
        if ($onlyTypes) {
            $where[] = '${dso.type} in (\''.implode("','", $onlyTypes).'\')';
        }
        //add clause to omit types
        if ($omitTypes) {
            $where[] = '${dso.type} not in (\''.implode("','", $omitTypes).'\')';
        }
        //join $where
        $search->where('('.implode(') AND (', $where).')');
        //execute search
        return $search;
    }

    protected function regexFilter($arr, $regex)
    {
        if (!$arr) {
            return [];
        }
        return array_filter(
            array_map(
                function ($e) use ($regex) {
                    if (!preg_match($regex, $e)) {
                        return false;
                    } else {
                        return $e;
                    }
                },
                $arr
            )
        );
    }

    /**
     * Traverses the graph from a given start point, calling $fn callback on
     * every node encountered. Traverses in a breadth-first search, constructing
     * an array of the result of the callback on each item.
     *
     * If no callback is provided, each item will simply be the ID of the noun.
     */
    public function traverse($start, $fn=null, $maxDepth=-1, $reverse=false)
    {
        $queue = [[0,$start,null]];
        $results = [];
        while ($queue) {
            list($depth, $id, $last) = array_shift($queue);
            if (!$id) {
                continue;
            }
            $r = $fn?$fn($id, $depth, $last):$id;
            $results[$id] = $r?$r:false;
            if ($depth != $maxDepth) {
                foreach (($reverse?$this->cms->helper('edges')->parents($id, 1):$this->cms->helper('edges')->children($id, 1)) as $c) {
                    if (!isset($results[$c])) {
                        $queue[] = [$depth+1,$c,$id];
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Works about the same as traverse(), but uses Nouns' built-in children()
     * and parents() methods. It's much slower, but respects any ordering or
     * filtering rules that are hard-coded into the nouns involved.
     */
    public function complexTraverse(Noun $start, $fn=null, $maxDepth=-1, $reverse=false)
    {
        $queue = [[0,$start,null]];
        $results = [];
        while ($queue) {
            list($depth, $noun, $last) = array_shift($queue);
            $r = $fn?$fn($noun, $depth, $last):$noun;
            $results[$noun] = $r?$r:false;
            if ($depth != $maxDepth) {
                foreach (($reverse?$noun->parents():$noun->children()) as $c) {
                    if (!isset($results[$c])) {
                        $queue[] = [$depth+1,$c,$noun];
                    }
                }
            }
        }
        return $results;
    }
}
