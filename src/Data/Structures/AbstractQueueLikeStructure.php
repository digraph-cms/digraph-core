<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Data\Structures;

use Digraph\Data\DatastoreNamespace;

abstract class AbstractQueueLikeStructure extends DatastoreNamespace
{
    protected $unique = true;

    /**
     * how to sort items when retrieving them for pull/peek
     */
    const SORT = 'data_id ASC';

    /**
     * specify whether this structure should only allow one copy of a value.
     *
     * @param boolean $set
     * @return void
     */
    public function unique(bool $set=null)
    {
        if ($set !== null) {
            $this->unique = $set;
        }
        return $this->unique;
    }

    /**
     * Each item inserted needs a name assigned to it. By default this is just
     * a uniqid generated by PHP. For something like a priority queue it could
     * be used to store priority information as well.
     *
     * @param mixed $value the value being added
     * @return string
     */
    protected function makeName($value)
    {
        if ($this->unique) {
            return md5(serialize($value));
        }else {
            return md5(uniqid());
        }
    }

    /**
     * Put an item into the data structure.
     *
     * @param mixed $value can be anything serializable to JSON
     * @return bool
     */
    public function put($value)
    {
        $name = $this->makeName($value);
        return $this->set(
            $name,
            $value
        );
    }

    /**
     * Pull a given number of items from the structure and delete them.
     *
     * @param integer $n
     * @return array
     */
    public function pull(int $n=1)
    {
        $values = $this->peek($n);
        foreach ($values as $name => $value) {
            $this->delete($name);
        }
        return $values;
    }

    /**
     * Convenience function to pull a single item and return just its value.
     *
     * @return mixed
     */
    public function pull1()
    {
        if ($v = $this->pull(1)) {
            return array_pop($v);
        }
        return null;
    }

    /**
     * Pull a given number of items from the structure and delete them.
     *
     * @param integer $n
     * @return array
     */
    public function peek(int $n=1)
    {
        return $this->datastore->query(
            $this->namespace,
            $n,
            static::SORT
        );
    }
}
