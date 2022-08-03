<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\UserField;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;

class ColumnUserFilteringHeader extends AbstractColumnFilteringHeader
{
    public function toolbox()
    {
        $form = $this->form();

        $user = (new UserField('Single user'))
            ->setID('user')
            ->setDefault($this->config('user'))
            ->addForm($form);

        $sort = (new Field('Sorting', new SELECT([
            false => 'None',
            'ASC' => 'Name A-Z',
            'DESC' => 'Name Z-A'
        ])))
            ->setID('sort')
            ->setDefault($this->config('sort'))
            ->addForm($form);

        $form->addCallback(function () use ($user, $sort) {
            $config = [];
            if ($user->value()) $config['user'] = $user->value();
            if ($sort->value()) $config['sort'] = $sort->value();
            throw new RedirectException($this->url($config ? $config : null));
        });

        return $form;
    }

    public function getJoinClauses(): array
    {
        if ($this->config('sort')) {
            return [
                'user on ' . $this->column() . ' = user.uuid'
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
                        'CASE WHEN user.name IS NULL THEN 0 ELSE 1 END',
                        'user.name ASC'
                    ];
                case 'DESC':
                    return [
                        'CASE WHEN user.name IS NULL THEN 1 ELSE 0 END',
                        'user.name DESC'
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
        if ($this->config('user')) {
            return [
                [$this->column(), $this->config('user')]
            ];
        } else return [];
    }
}
