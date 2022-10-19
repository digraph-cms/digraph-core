<?php

namespace DigraphCMS\Datastore;

use Flatrr\FlatArray;

class DatastoreGroup
{
    protected $namespace;
    protected $name;

    public function __construct(string $namespace, string $name)
    {
        $namespace = Datastore::sanitize($namespace);
        $name = Datastore::sanitize($name);
        $this->namespace = new DatastoreNamespace($namespace);
        $this->name = $name;
    }

    /**
     * @param string $key
     * @param string|null $value
     * @param array|FlatArray|null $data
     * @return DatastoreItem
     */
    public function set(string $key, ?string $value, $data = null): DatastoreItem
    {
        return $this->namespace()->set($this->name, $key, $value, $data);
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function exists(string $key): bool
    {
        return $this->namespace()->exists($this->name, $key);
    }

    /**
     * @param string $key
     * @return string|null|false
     */
    public function value(string $key)
    {
        return $this->namespace()->value($this->name, $key);
    }

    public function namespace(): DatastoreNamespace
    {
        return $this->namespace;
    }

    public function select(): DatastoreSelect
    {
        return $this->namespace()->select()
            ->where('grp', $this->name);
    }
}
