<?php

namespace DigraphCMS\DB;

class SubQuery
{
    protected $value, $select;
    public function __construct($value, AbstractMappedSelect $select)
    {
        $this->value = $value;
        $this->select = $select;
    }

    public function value()
    {
        return $this->value;
    }

    public function select(): AbstractMappedSelect
    {
        return $this->select;
    }
}
