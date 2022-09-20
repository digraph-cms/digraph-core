<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;

class ColumnStringFilteringHeader extends AbstractColumnFilteringHeader
{
    public function toolbox()
    {
        $form = $this->form();

        $query = (new Field('Contains'))
            ->setID('q')
            ->setDefault($this->config('q'))
            ->addForm($form);

        $sort = (new Field('Sorting', new SELECT([
            false => 'None',
            'ASC' => 'Sort A-Z',
            'DESC' => 'Sort Z-A'
        ])))
            ->setID('sort')
            ->setDefault($this->config('sort'))
            ->addForm($form);

        $form->addCallback(function () use ($query, $sort) {
            $config = [];
            if ($query->value()) $config['q'] = $query->value();
            if ($sort->value()) $config['sort'] = $sort->value();
            throw new RedirectException($this->url($config ? $config : null));
        });

        return $form;
    }

    public function getJoinClauses(): array
    {
        if ($this->config('sort')) {
            return [
                'page on ' . $this->column() . ' = page.uuid'
            ];
        } else {
            return [];
        }
    }

    public function getOrderClauses(): array
    {
        if ($this->config('sort')) {
            switch ($this->config('sort')) {
                case 'ASC':
                    return [
                        'CASE WHEN ' . $this->column() . ' IS NULL THEN 0 ELSE 1 END',
                        $this->column() . ' ASC'
                    ];
                case 'DESC':
                    return [
                        'CASE WHEN ' . $this->column() . ' IS NULL THEN 1 ELSE 0 END',
                        $this->column() . ' DESC'
                    ];
                default:
                    return [];
            }
        } else {
            return [];
        }
    }

    public function getWhereClauses(): array
    {
        if ($this->config('q')) {
            return [
                [
                    $this->column() . ' LIKE ?',
                    [AbstractMappedSelect::prepareLikePattern($this->config('q'))]
                ]
            ];
        } else return [];
    }
}
