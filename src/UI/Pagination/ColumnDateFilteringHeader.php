<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\DateField;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Format;

class ColumnDateFilteringHeader extends AbstractColumnFilteringHeader
{
    protected $column;

    public function __construct(
        string $label,
        string $column
    ) {
        parent::__construct($label);
        // save config
        $this->column = $column;
        $this->id = 'd_' . crc32($column);
    }

    public function toolbox()
    {
        $form = $this->form();

        $start = (new DateField('Start date'))
            ->setID('start')
            ->setDefault(@$this->config()['start'] ? Format::parseDate($this->config()['start']) : null)
            ->addForm($form);

        $end = (new DateField('End date'))
            ->setID('end')
            ->setDefault(@$this->config()['end'] ? Format::parseDate($this->config()['end']) : null)
            ->addForm($form);

        $sort = (new Field('Sorting', new SELECT([
            false => 'None',
            'ASC' => 'Oldest first',
            'DESC' => 'Newest first'
        ])))
            ->setID('sort')
            ->setDefault(@$this->config()['sort'])
            ->addForm($form);

        $form->addCallback(function () use ($start, $end, $sort) {
            $config = [];
            if ($start->value()) $config['start'] = $start->value()->getTimestamp();
            if ($end->value()) $config['end'] = $end->value()->setTime(23, 59, 59)->getTimestamp();
            if ($sort->value()) $config['sort'] = $sort->value();
            throw new RedirectException($this->url($config ? $config : null));
        });

        return $form;
    }

    public function getOrderClauses(): array
    {
        switch (@$this->config()['sort']) {
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
        $clauses = [];
        if ($this->config()['start']) $clauses[] = [$this->column . ' >= ?', [$this->config()['start']]];
        if ($this->config()['end']) $clauses[] = [$this->column . ' <= ?', [$this->config()['end']]];
        return $clauses;
    }
}
