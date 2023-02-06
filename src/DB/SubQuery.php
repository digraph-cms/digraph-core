<?php

namespace DigraphCMS\DB;

class SubQuery
{
    /** @var string */
    protected $value;
    /** @var AbstractMappedSelect */
    protected $select;

    public function __construct(string $value, AbstractMappedSelect $select)
    {
        $this->value = $value;
        $this->select = $select;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function select(): AbstractMappedSelect
    {
        return $this->select;
    }
}
