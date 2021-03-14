<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Data;

class DatastoreNamespace
{
    protected $namespace;
    protected $datastore;

    public function __construct(string $namespace, $datastore)
    {
        $this->namespace = $namespace;
        $this->datastore = $datastore;
    }

    /**
     * Get a single value by namespace and name
     *
     * @param string $name
     * @return ?array
     */
    public function get(string $name)
    {
        return $this->datastore->get(
            $this->namespace,
            $name
        );
    }

    /**
     * Get all keys containing the given value
     *
     * @param string $value
     * @param integer $limit
     * @param string $sort
     * @return void
     */
    public function query(string $value, int $limit = null, string $sort = 'data_id DESC')
    {
        return $this->datastore->query(
            $this->namespace,
            $value,
            $limit,
            $sort
        );
    }

    /**
     * Get all key/value pairs up to $limit
     *
     * @param integer $limit
     * @param string $sort
     * @return void
     */
    public function getN(int $limit = null, string $sort = 'data_id DESC')
    {
        return $this->datastore->getN(
            $this->namespace,
            $limit,
            $sort
        );
    }

    /**
     * Get all values in a given namespace
     *
     * @return array
     */
    public function getAll()
    {
        return $this->datastore->getAll(
            $this->namespace
        );
    }

    /**
     * Create or update a value by namespace and name.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function set(string $name, $value)
    {
        return $this->datastore->set(
            $this->namespace,
            $name,
            $value
        );
    }

    /**
     * Unset a value my namespace and name
     *
     * @param string $name
     * @return bool
     */
    public function delete(string $name)
    {
        return $this->datastore->delete(
            $this->namespace,
            $name
        );
    }

    /**
     * Delete all values in a namespace
     *
     * @param string $name
     * @return bool
     */
    public function deleteNamespace()
    {
        return $this->datastore->deleteNamespace(
            $this->namespace
        );
    }
}
