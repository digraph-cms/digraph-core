<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Data;

use Digraph\Helpers\AbstractHelper;

class DatastoreHelper extends AbstractHelper
{
    protected $pdo;

    /* DDL for table */
    const DDL_DATASTORE = <<<EOT
CREATE TABLE IF NOT EXISTS digraph_datastore (
    data_id INTEGER PRIMARY KEY,
    data_namespace TEXT NOT NULL,
    data_name TEXT NOT NULL,
    data_value TEXT
);
EOT;

    /* indexes to create on table */
    const IDX = [
        'CREATE INDEX IF NOT EXISTS digraph_datastore_namespace_IDX ON digraph_datastore (data_namespace);',
        'CREATE INDEX IF NOT EXISTS digraph_datastore_name_IDX ON digraph_datastore (data_name);',
        'CREATE INDEX IF NOT EXISTS digraph_datastore_value_IDX ON digraph_datastore (data_value);',
        'CREATE UNIQUE INDEX IF NOT EXISTS digraph_datastore_UNIQUE_IDX ON digraph_datastore (data_namespace,data_name);'
    ];

    public function queue(string $namespace)
    {
        return new Structures\Queue('queue_'.$namespace, $this);
    }

    public function pqueue(string $namespace)
    {
        return new Structures\PriorityQueue('pqueue_'.$namespace, $this);
    }

    public function stack(string $namespace)
    {
        return new Structures\Stack('stack_'.$namespace, $this);
    }

    /**
     * Get a convenience object that allows making get()/set()/delete() calls without specifying a
     * namespace.
     *
     * @param string $namespace namespace name
     * @return DatastoreNamespace
     */
    public function namespace(string $namespace)
    {
        return new DatastoreNamespace($namespace, $this);
    }

    /**
     * Get a single value by namespace and name
     *
     * @param string $namespace
     * @param string $name
     * @return ?array
     */
    public function get(string $namespace, string $name)
    {
        $sql = 'SELECT * FROM digraph_datastore WHERE data_namespace = :namespace AND data_name = :name;';
        $res = $this->fetch($sql, [
            'namespace' => $namespace,
            'name' => $name
        ]);
        if ($res) {
            return array_pop($res);
        } else {
            return null;
        }
    }

    /**
     * Retrieve a specific number of values, with an optional sorting rule (defaults
     * to providing the most recent entries as sorted/identified by primary key)
     *
     * @param string $namespace
     * @param int $limit the maximum to return (0/null returns all)
     * @param string $sort SQL sorting rules
     */
    public function query(string $namespace, int $limit=null, string $sort='data_id DESC')
    {
        $sql = 'SELECT * FROM digraph_datastore';
        $sql .= ' WHERE data_namespace = :namespace';
        $sql .= ' ORDER BY '.$sort;
        if ($limit) {
            $sql .= ' LIMIT '.$limit;
        }
        return $this->fetch($sql, [
            'namespace' => $namespace
        ]);
    }

    /**
     * Get all values in a given namespace
     *
     * @param string $namespace
     * @return array
     */
    public function getAll(string $namespace)
    {
        $sql = 'SELECT * FROM digraph_datastore WHERE data_namespace = :namespace';
        return $this->fetch($sql, [
            'namespace' => $namespace
        ]);
    }

    /**
     * Create or update a value by namespace and name.
     *
     * @param string $namespace
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function set(string $namespace, string $name, $value)
    {
        $args = [
            'namespace' => $namespace,
            'name' => $name,
            'value' => json_encode($value)
        ];
        //insert, just trying to insert should be basically as fast as checking
        $sql = 'INSERT INTO digraph_datastore (data_namespace,data_name,data_value) VALUES (:namespace,:name,:value);';
        if (!$this->execute($sql, $args)) {
            //if insert failed, try to update existing row
            $sql = 'UPDATE digraph_datastore SET data_value = :value WHERE data_namespace = :namespace AND data_name = :name;';
            if (!$this->execute($sql, $args)) {
                return false;
            }
        }
        //it must have worked
        return true;
    }

    /**
     * Unset a value my namespace and name
     *
     * @param string $namespace
     * @param string $name
     * @return bool
     */
    public function delete(string $namespace, string $name)
    {
        $sql = 'DELETE FROM digraph_datastore WHERE data_namespace = :namespace AND data_name = :name;';
        return $this->execute($sql, [
            'namespace' => $namespace,
            'name' => $name
        ]);
    }

    /**
     * Delete all values in a namespace
     *
     * @param string $namespace
     * @param string $name
     * @return bool
     */
    public function deleteNamespace(string $namespace)
    {
        $sql = 'DELETE FROM digraph_datastore WHERE data_namespace = :namespace;';
        return $this->execute($sql, [
            'namespace' => $namespace
        ]);
    }

    /**
     * Execute SQL and return whether or not it succeeded.
     *
     * @param string $sql
     * @param array $args
     * @return bool
     */
    protected function execute($sql, $args)
    {
        $fargs = [];
        foreach ($args as $key => $value) {
            $fargs[':'.$key] = $value;
        }
        $s = $this->pdo->prepare($sql);
        if (!$s) {
            throw new \Exception('PDO prepare error: '.implode(', ', $this->pdo->errorInfo()));
        }
        return $s->execute($fargs);
    }

    /**
     * Execute SQL and return associative array of data_name/data_value pairs
     *
     * @param string $sql
     * @param array $args
     * @return array
     */
    protected function fetch($sql, $args)
    {
        $fargs = [];
        foreach ($args as $key => $value) {
            $fargs[':'.$key] = $value;
        }
        $s = $this->pdo->prepare($sql);
        $res = [];
        if ($s->execute($fargs)) {
            foreach ($s->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $res[$row['data_name']] = json_decode($row['data_value'], true);
            }
        }
        return $res;
    }

    public function construct()
    {
        //uses sqlite-only pdo
        $this->pdo = $this->cms->pdo('datastore');
        //set up JSON function from Destructr
        // $this->pdo->sqliteCreateFunction(
        //     'DSJSON',
        //     '\\Destructr\\LegacyDrivers\\SQLiteDriver::JSON_EXTRACT',
        //     2
        // );
        //ensure that tables and indexes exist
        $this->pdo->exec(static::DDL_DATASTORE);
        foreach (static::IDX as $idx) {
            $this->pdo->exec($idx);
        }
    }
}
