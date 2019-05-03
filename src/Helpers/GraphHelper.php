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
    public function children($id)
    {
        return $this->cms->helper('edges')->children($id);
    }

    public function parents($id)
    {
        return $this->cms->helper('edges')->parents($id);
    }

    public function route($start, $end, $reverse=false) : ?array
    {
        /*
        It's noteworthy that  this method works in a slightly unintuitive way.
        It actually calls traverse in such a way that it searches always
        searches backwards up the tree of possible routes. This is because most
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
            $r = $fn?$fn($id, $depth, $last):$id;
            $results[$id] = $r?$r:false;
            if ($depth != $maxDepth) {
                foreach (($reverse?$this->parents($id, 1):$this->children($id, 1)) as $c) {
                    if (!isset($results[$c])) {
                        $queue[] = [$depth+1,$c,$id];
                    }
                }
            }
        }
        return $results;
    }
}
