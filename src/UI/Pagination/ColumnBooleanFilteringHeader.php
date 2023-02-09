<?php

namespace DigraphCMS\UI\Pagination;

class ColumnBooleanFilteringHeader extends AbstractColumnFilteringHeader
{
    protected $trueText, $falseText;

    public function __construct(
        string $label,
        string $column,
        string $trueText = 'Is true',
        string $falseText = 'Is false'
    ) {
        parent::__construct($label, $column);
        // save config
        $this->trueText = $trueText;
        $this->falseText = $falseText;
    }

    public function toolbox()
    {
        return implode('<br>', [
            $this->link(true, $this->trueText),
            $this->link(false, $this->falseText)
        ]);
    }

    public function getOrderClauses(): array
    {
        return [];
    }

    public function getWhereClauses(): array
    {
        $column = $this->column();
        if ($this->config() === true) return [["$column", [true]]];
        elseif ($this->config() === false) return [["(NOT $column OR $column is null)", []]];
        else return [];
    }

    public function getJoinClauses(): array
    {
        return [];
    }
}
