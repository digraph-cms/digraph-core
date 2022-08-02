<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\A;
use DigraphCMS\HTML\Icon;

class ColumnSortingHeader extends AbstractColumnFilteringHeader
{
    protected $column, $ascText, $descText;

    public function __construct(
        string $label,
        string $column,
        string $ascText = 'Sort ascending',
        string $descText = 'Sort descending'
    ) {
        parent::__construct($label);
        // save config
        $this->column = $column;
        $this->id = 's_' . crc32($column);
        $this->ascText = $ascText;
        $this->descText = $descText;
    }

    public function statusIcon(): string
    {
        switch ($this->config()) {
            case 'ASC':
                return new Icon('sort', 'Sorted ascending');
            case 'DESC':
                return (new Icon('sort', 'Sorted descending'))
                    ->setStyle('transform', 'scaleY(-1)');
            default:
                return '';
        }
    }

    public function toolbox()
    {
        return implode('<br>', [
            $this->link('ASC', $this->ascText),
            $this->link('DESC', $this->descText)
        ]);
    }

    protected function link(?string $order, $text): A
    {
        return (new A($this->section->url($this->getFilterID(), $order)))
            ->setData('target', '_frame')
            ->addClass('column-sort')
            ->setStyle('white-space', 'nowrap')
            ->addChild($text);
    }

    public function getOrderClauses(): array
    {
        switch ($this->config()) {
            case 'ASC':
                return [
                    'CASE WHEN ' . $this->column . ' IS NULL THEN 0 ELSE 1 END',
                    $this->column . ' ASC'
                ];
            case 'DESC':
                return [
                    'CASE WHEN ' . $this->column . ' IS NULL THEN 1 ELSE 0 END',
                    $this->column . ' DESC'
                ];
            default:
                return [];
        }
    }

    public function getWhereClauses(): array
    {
        return [];
    }
}
