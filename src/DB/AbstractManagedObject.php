<?php
namespace DigraphCMS\DB;

abstract class AbstractManagedObject {
    const OBJECT_MANAGER = null;

    public function insert()
    {
        return (static::OBJECT_MANAGER)::insert($this);
    }

    public function update()
    {
        return (static::OBJECT_MANAGER)::update($this);
    }

    public function delete()
    {
        return (static::OBJECT_MANAGER)::delete($this);
    }
}