<?php

namespace DigraphCMS\DB;

class SubQuery
{
    /** @var mixed */
    protected $value;
    /** @var AbstractMappedSelect */
    protected $select;

    public function __construct(mixed $value, AbstractMappedSelect $select)
    {
        $this->value = $value;
        $this->select = $select;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function select(): AbstractMappedSelect
    {
        return $this->select;
    }
}
