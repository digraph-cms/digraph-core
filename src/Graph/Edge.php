<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Graph;

class Edge
{
    protected $row;

    public function __construct(array $row)
    {
        $this->row = $row;
    }

    public function start()
    {
        return $this->row['edge_start'];
    }

    public function end()
    {
        return $this->row['edge_end'];
    }

    public function type()
    {
        return $this->row['edge_type'];
    }

    public function weight()
    {
        return $this->row['edge_weight'];
    }

    public function id()
    {
        return $this->row['edge_id'];
    }
}
