<?php

namespace DigraphCMS\Datastore;

use Flatrr\FlatArray;

class DatastoreNamespace
{
    protected $name;

    public function __construct(string $name)
    {
        $name = Datastore::sanitize($name);
        $this->name = $name;
    }

    /**
     * @param string $group
     * @param string $key
     * @param string|null $value
     * @param array|FlatArray|null $data
     * @return DatastoreItem
     */
    public function set(string $group, string $key, ?string $value, $data = null): DatastoreItem
    {
        return Datastore::set($this->name, $group, $key, $value, $data);
    }

    /**
     * @param string $group
     * @param string $key
     * @return boolean
     */
    public function exists(string $group, string $key): bool
    {
        return Datastore::exists($this->name, $group, $key);
    }

    /**
     * @param string $group
     * @param string $key
     * @return string|null|false
     */
    public function value(string $group, string $key)
    {
        return Datastore::value($this->name, $group, $key);
    }

    /**
     * @param string $group
     * @param string $key
     * @return boolean
     */
    public function delete(string $group, string $key): bool
    {
        return Datastore::delete($this->name, $group, $key);
    }

    public function select(): DatastoreSelect
    {
        return (new DatastoreSelect)->where('ns', $this->name);
    }
}
