<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Graph;

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
    const DEFAULT_FIND_OPTS = [
        'depth' => -1,
        'onlyTypes' => [],
        'omitTypes' => [],
        'sort' => 'tree'
    ];

    /**
     * Traverse upward and locate the nearest ancestor matching a test. Test can
     * be a string, and will be matched against DSO type. A callback can also be
     * provided, which will be given a Noun and should return true for a positive
     * match.
     *
     * @param string $start DSO ID of starting point
     * @param mixed $fnOrDSOType DSO type or function for identifying targets
     * @param boolean $reverse reverse order (searches up by default)
     * @return ?\Digraph\DSO\Noun
     */
    public function nearest($start, $fnOrDSOType, $reverse=false)
    {
        $found = null;
        $this->traverse(
            $start,
            function ($id) use (&$found,$fnOrDSOType) {
                if ($noun = $this->cms->read($id)) {
                    if (is_callable($fnOrDSOType)) {
                        if ($fnOrDSOType($noun)) {
                            $found = $noun;
                            return false;
                        }
                    } else {
                        if ($noun['dso.type'] == $fnOrDSOType) {
                            $found = $noun;
                            return false;
                        }
                    }
                }
            },
            null,
            -1,
            !$reverse
        );
        return $found;
    }

    /*
    Wrapper for childIDs that actually loads all the Nouns from the database.
    Maintains breadth-first order.
     */
    public function children($id, $type=null, $depth=1, $order=null)
    {
        return $this->sqlResults($this->childIDs($id, $type, $depth), $order);
    }

    /*
    Wrapper for parentIDs that actually loads all the Nouns from the database.
    Maintains breadth-first order.
     */
    public function parents($id, $type=null, $depth=1, $order=null)
    {
        return $this->sqlResults($this->parentIDs($id, $type, $depth), $order);
    }

    /*
    Return all children of a given ID, optionally up to a given depth and/or of
    a specific edge type. Returns the IDs only.
     */
    public function childIDs($id, $type=null, $depth=1)
    {
        $out = $this->traverse($id, null, $type, $depth);
        array_shift($out);//shift first item off to not include root of search
        return $out;
    }

    /*
    Return all parents of a given ID, optionally up to a given depth and/or of
    a specific edge type. Returns the IDs only.
     */
    public function parentIDs($id, $type=null, $depth=1)
    {
        $out = $this->traverse($id, null, $type, $depth, true);
        array_shift($out);//shift first item off to not include root of search
        return $out;
    }

    /*
    Wrapper for routeIDs that actually loads all the Nouns from the database.
    Maintains breadth-first order.
     */
    public function route($start, $end, $reverse=false)
    {
        return $this->sqlResults($this->routeIDs($start, $end, $reverse));
    }

    /*
    Get an array of IDs that form a route between a given start and end point.
     */
    public function routeIDs($start, $end) : ?array
    {
        /*
        It's noteworthy that this method works in a slightly unintuitive way.
        It actually calls traverse in such a way that its searches always
        run backwards up the tree of possible routes. This is because most
        of the time nouns will have more children than parents, so going
        backwards will yield a much smaller tree.
         */
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

    protected function sqlResults($ids=[], $order=null)
    {
        if ($s = $this->sqlSearch($ids)) {
            if ($order === null) {
                //for null ordering, order by the order of the id array
                $ids = array_flip($ids);
                foreach ($s->execute() as $n) {
                    $ids[$n['dso.id']] = $n;
                }
                return array_filter(
                    $ids,
                    function ($e) {
                        return $e instanceof Noun;
                    }
                );
            } else {
                //for specified ordering, let SQL do our ordering
                $s->order($order);
                return $s->execute();
            }
        } else {
            return [];
        }
    }

    /**
     * Creates a Destructr search object set up to return a given list of IDs,
     * optionally limiting to and omitting by type
     */
    protected function sqlSearch($ids=[])
    {
        //sanitize IDs
        $ids = $this->regexFilter($ids, '/^[a-z0-9]+$/');
        //short circuit if none were valid
        if (!$ids) {
            return null;
        }
        //use DSO search
        $search = $this->cms->factory()->search();
        //set up where clause
        $ids = array_map([$search, 'quote'], $ids);
        $search->where('${dso.id} in ('.implode(",", $ids).')');
        //return search object
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
    public function traverse($start, $fn=null, $type=null, $maxDepth=-1, $reverse=false)
    {
        $queue = [[0,$start,null]];
        $results = [];
        while ($queue) {
            list($depth, $id, $last) = array_shift($queue);
            if (!$id || isset($results[$id])) {
                continue;
            }
            $r = $fn?$fn($id, $depth, $last):$id;
            $results[$id] = $r?$r:false;
            //if result is false, abort traverse
            //this means callback should return null if it doesn't want to abort
            if ($r === false) {
                return $results;
            }
            if ($depth != $maxDepth) {
                if ($reverse) {
                    $new = $this->cms->helper('edges')->parents($id, $type, true);
                } else {
                    $new = $this->cms->helper('edges')->children($id, $type, true);
                }
                foreach ($new as $n) {
                    $queue[] = [$depth+1,$n,$id];
                }
            }
        }
        return $results;
    }
}
