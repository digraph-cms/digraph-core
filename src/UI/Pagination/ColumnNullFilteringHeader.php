<?php

namespace DigraphCMS\UI\Pagination;

class ColumnNullFilteringHeader extends AbstractColumnFilteringHeader
{
    protected $notNullText, $isNullText;

    public function __construct(
        string $label,
        string $column,
        string $notNullText = 'Is not empty',
        string $isNullText = 'Is empty'
    ) {
        parent::__construct($label, $column);
        // save config
        $this->notNullText = $notNullText;
        $this->isNullText = $isNullText;
    }

    public function toolbox()
    {
        return implode('<br>', [
            $this->link(true, $this->notNullText),
            $this->link(false, $this->isNullText)
        ]);
    }

    public function getOrderClauses(): array
    {
        return [];
    }

    public function getWhereClauses(): array
    {
        if ($this->config() === true) return [[$this->column() . ' IS NOT NULL', []]];
        elseif ($this->config() === false) return [[$this->column() . ' IS NULL', []]];
        else return [];
    }

    public function getLikeClauses(): array
    {
        return [];
    }

    public function getJoinClauses(): array
    {
        return [];
    }
}
