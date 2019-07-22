<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Data;

use Digraph\DSO\Noun;

/**
 * TODO: deprecate this class in favor of DatastoreHelper
 */
class Fact
{
    protected $row;
    protected $group;

    public function __construct(array $row, FactGroup &$group)
    {
        $this->group = $group;
        $this->row = $row;
    }

    public function namespace()
    {
        return $this->row['fact_namespace'];
    }

    public function id()
    {
        return $this->row['fact_id'];
    }

    public function about($set=null)
    {
        if ($set !== null) {
            if ($set === false) {
                $set = null;
            }
            if ($set instanceof Noun) {
                $set = $set['dso.id'];
            }
            $this->row['fact_about'] = $set;
        }
        return $this->row['fact_about'];
    }

    public function name(string $set=null)
    {
        if ($set !== null) {
            $this->row['fact_name'] = $set;
        }
        return $this->row['fact_name'];
    }

    public function value(string $set=null)
    {
        if ($set !== null) {
            $this->row['fact_value'] = $set;
        }
        return $this->row['fact_value'];
    }

    public function order($set=null)
    {
        if ($set !== null) {
            $this->row['fact_order'] = $set;
        }
        return $this->row['fact_order'];
    }

    public function data($set=null)
    {
        if ($set !== null) {
            $this->row['fact_data'] = json_encode($set);
        }
        return json_decode($this->row['fact_data'], true);
    }
}
