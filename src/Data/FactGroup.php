<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Data;

/**
 * TODO: deprecate this class in favor of DatastoreHelper
 */
class FactGroup
{
    protected $helper;
    protected $pdo;
    protected $namespace;

    public function __construct(&$helper, &$pdo, $namespace)
    {
        $this->helper = $helper;
        $this->pdo = $pdo;
        $this->namespace = $namespace;
    }

    public function list($reverseID=false, $reverseOrder=false)
    {
        $args = [
            'fact_namespace' => $this->namespace
        ];
        $where = [
            'fact_namespace = :fact_namespace'
        ];
        $order = [
            'fact_order '.($reverseOrder?'DESC':'ASC'),
            'fact_id '.($reverseID?'DESC':'ASC')
        ];
        $sql = 'SELECT * FROM digraph_facts WHERE '.implode(' AND ', $where).' ORDER BY '.implode(', ', $order);
        return $this->fetch($sql, $args);
    }

    public function delete(Fact $fact)
    {
        if ($this->namespace != $fact->namespace()) {
            return false;
        }
        $args = [
            'fact_id' => $fact->id()
        ];
        $sql = 'DELETE FROM digraph_facts WHERE fact_id = :fact_id';
        return $this->execute($sql, $args);
    }

    public function update(Fact $fact)
    {
        $args = [
            'fact_value' => $fact->value(),
            'fact_order' => $fact->order(),
            'fact_data' => json_encode($fact->data),
            'fact_id' => $fact->id()
        ];
        $set = [
            'fact_value = :fact_value',
            'fact_order = :fact_order',
            'fact_data = :fact_data'
        ];
        $sql = 'UPDATE digraph_facts SET '.implode(', ', $set).' WHERE fact_id = :fact_id';
        return $this->execute($sql, $args);
    }

    public function create($name, $value, $about = null, $data = null, $order=null)
    {
        $args = [
            'fact_namespace' => $this->namespace,
            'fact_about' => $about,
            'fact_name' => $name,
            'fact_value' => $value,
            'fact_order' => $order,
            'fact_data' => json_encode($data)
        ];
        $cols = implode(',', array_keys($args));
        $values = ':'.implode(',:', array_keys($args));
        $sql = 'INSERT INTO digraph_facts ('.$cols.') VALUES ('.$values.')';
        return $this->execute($sql, $args);
    }

    protected function execute($sql, $args)
    {
        $fargs = [];
        foreach ($args as $key => $value) {
            $fargs[':'.$key] = $value;
        }
        $s = $this->pdo->prepare($sql);
        return $s->execute($fargs);
    }

    protected function fetch($sql, $args)
    {
        $fargs = [];
        foreach ($args as $key => $value) {
            $fargs[':'.$key] = $value;
        }
        $s = $this->pdo->prepare($sql);
        if ($s->execute($fargs)) {
            return array_map(
                function ($e) {
                    return new Fact($e, $this);
                },
                $s->fetchAll(\PDO::FETCH_ASSOC)
            );
        } else {
            return [];
        }
    }
}
