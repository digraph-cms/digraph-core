<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\PageField;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;

class ColumnPageFilteringHeader extends AbstractColumnFilteringHeader
{
    public function toolbox()
    {
        $form = $this->form();

        $page = (new PageField('Pick page'))
            ->setID('page')
            ->setDefault($this->config('page'))
            ->addForm($form);

        $sort = (new Field('Sorting', new SELECT([
            false => 'None',
            'ASC' => 'Name A-Z',
            'DESC' => 'Name Z-A'
        ])))
            ->setID('sort')
            ->setDefault($this->config('sort'))
            ->addForm($form);

        $form->addCallback(function () use ($page, $sort) {
            $config = [];
            if ($page->value()) $config['page'] = $page->value();
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
                        'CASE WHEN page.name IS NULL THEN 0 ELSE 1 END',
                        'page.name ASC'
                    ];
                case 'DESC':
                    return [
                        'CASE WHEN page.name IS NULL THEN 1 ELSE 0 END',
                        'page.name DESC'
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
        if ($this->config('page')) {
            return [
                [$this->column(), $this->config('page')]
            ];
        } else return [];
    }
}
