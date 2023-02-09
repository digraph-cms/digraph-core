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
        if (str_contains($column, '${')) {
            // this is a JSON column, so true/false are strings
            if ($this->config() === true) return [["$column", ['true']]];
            elseif ($this->config() === false) return [["($column <> ? AND $column is not null)", ['true']]];
            else return [];
        } else {
            // this is a native column
            if ($this->config() === true) return [["$column", []]];
            elseif ($this->config() === false) return [["($column is null OR (NOT $column))", []]];
            else return [];
        }
    }

    public function getJoinClauses(): array
    {
        return [];
    }
}
