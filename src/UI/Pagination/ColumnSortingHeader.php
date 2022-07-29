<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\A;

class ColumnSortingHeader extends ColumnHeader implements FilterToolInterface
{
    protected static $idCounter = 0;
    protected $column;
    protected $paginator;

    /**
     * @param string $label
     * @param string $column
     * @param string|null $id
     */
    public function __construct(string $label, string $column, string $id = null)
    {
        parent::__construct($label);
        // save ID and column to be used for sorting
        $this->column = $column;
        $this->id = $id ?? 's' . self::$idCounter++;
    }

    protected function classes(): array
    {
        $config = $this->paginator->getToolConfig($this->getFilterID());
        $classes = ['sortable'];
        if ($config == 'ASC') {
            $classes[] = 'sorted';
            $classes[] = 'sorted--asc';
        }
        if ($config == 'DESC') {
            $classes[] = 'sorted';
            $classes[] = 'sorted--desc';
        }
        if ($config === null) $classes[] = 'unsorted';
        return $classes;
    }

    protected function headerContent(): string
    {
        return sprintf(
            '%s<span class="column-sorter">%s%s%s</span>',
            $this->label,
            $this->link('ASC', '[a..z]', 'column-sort--asc'),
            $this->link('DESC', '[z..a]', 'column-sort--desc'),
            $this->link(null, '[x]', 'column-sort--clear')
        );
    }

    protected function link(?string $order, $text, $class): A
    {
        $link = (new A($this->paginator->url($this->getFilterID(), $order)))
            ->setData('target', '_frame')
            ->addClass('column-sort')
            ->addClass($class)
            ->addChild($text);
        return $link;
    }

    public function setPaginator(PaginatedSection $paginator)
    {
        $this->paginator = $paginator;
    }

    public function getOrderClauses(): array
    {
        $config = $this->paginator->getToolConfig($this->getFilterID());
        if ($config == 'ASC') {
            return [
                'CASE WHEN ' . $this->column . ' IS NULL THEN 0 ELSE 1 END',
                $this->column . ' ASC'
            ];
        } elseif ($config == 'DESC') {
            return [
                'CASE WHEN ' . $this->column . ' IS NULL THEN 1 ELSE 0 END',
                $this->column . ' DESC'
            ];
        } else {
            return [];
        }
    }

    public function getWhereClauses(): array
    {
        return [];
    }

    public function getFilterID(): string
    {
        return 'sort_' . $this->id;
    }
}
