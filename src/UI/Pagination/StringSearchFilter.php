<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\HTTP\RedirectException;

class StringSearchFilter extends FormWrapper implements FilterToolInterface
{
    /** @var PaginatedSection */
    protected $section;
    /** @var string */
    protected $column;
    /** @var INPUT */
    protected $input;

    public function __construct(string $column)
    {
        parent::__construct();
        $this->column = $column;
        $this->input = new INPUT('Search');
        $this->addChild($this->input);
        $this->button()->setText('Search');
        $this->addClass('inline-form');
        $this->addCallback(function () {
            throw new RedirectException(
                $this->section->url($this->getFilterID(), ['q' => $this->input->value()])
            );
        });
    }

    public function children(): array
    {
        $children = parent::children();
        if ($this->input->default()) {
            $children[] = sprintf(
                '<a href="%s" class="button button--warning button--inverted" data-target="_frame">Clear</a>',
                $this->section->url($this->getFilterID(), null)
            );
        }
        return $children;
    }

    public function getWhereClauses(): array
    {
        $out = array_map(
            function (string $word): array {
                return [
                    $this->column . ' LIKE ?',
                    [AbstractMappedSelect::prepareLikePattern($word)]
                ];
            },
            $this->getQueryTerms()
        );
        return $out;
    }

    public function getQueryTerms(): array
    {
        $q = @$this->section->getToolConfig($this->getFilterID())['q'] ?? '';
        $query = strtolower(trim($q));
        $query = preg_split('/\s+/', $query);
        $query = array_filter($query, function ($e) {
            return strlen($e) >= 3;
        });
        $query = array_unique($query);
        return $query;
    }

    public function getJoinClauses(): array
    {
        return [];
    }

    public function getOrderClauses(): array
    {
        return [];
    }

    public function input(): INPUT
    {
        return $this->input;
    }

    public function setSection(PaginatedSection $section)
    {
        $this->section = $section;
        $this->input->setDefault(@$this->section->getToolConfig($this->getFilterID())['q']);
    }

    public function getFilterID(): string
    {
        return 's' . crc32($this->id());
    }
}
