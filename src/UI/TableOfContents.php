<?php

namespace DigraphCMS\UI;

use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;

class TableOfContents extends Tag
{
    protected $tag = 'ul';
    protected $page;
    protected $edge_types = null;
    protected $ignore_sort_order = null;
    protected $firstPage = 20;
    protected $perPage = 10;
    protected $parents = [];
    protected $depth;

    public function __construct(AbstractPage $page, null|string|array $edge_types = null, null|bool $ignore_sort_order = null, int $depth = null, array $parents = [])
    {
        $this->page = $page;
        $this->parents = $parents;
        $this->parents[] = $page->uuid();
        $this->depth = $depth;
        $this->edge_types = $edge_types;
        $this->ignore_sort_order = $ignore_sort_order;
    }

    public function classes(): array
    {
        return array_merge(
            [
                'table-of-contents',
                'navigation-frame',
                'navigation-frame--stateless',
            ],
            parent::classes()
        );
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'data-target' => '_top',
            ]
        );
    }

    public function id(): ?string
    {
        return parent::id() ?? 'table-of-contents--' . $this->page->uuid();
    }

    public function children(): array
    {
        return array_merge(
            parent::children(),
            $this->generateItems(),
            [$this->page() < $this->maxPage() ? $this->generateMoreLink() : '']
        );
    }

    public function page(): int
    {
        $page = intval(Context::arg($this->arg()) ?? 1);
        if ($page > $this->maxPage()) $page = $this->maxPage();
        elseif ($page < 1) $page = 1;
        return $page;
    }

    public function maxPage(): int
    {
        $count = $this->page->children()->count();
        if ($count <= $this->firstPage) return 1;
        else return intval(ceil(($count - $this->firstPage) / $this->perPage) + 1);
    }

    public function generateMoreLink(): string
    {
        $url = Context::url();
        $url->arg($this->arg(), strval($this->page() + 1));
        return sprintf(
            '<li class="table-of-contents__load-more"><a href="%s" data-target="_frame">-- load more --</a></li>',
            $url,
        );
    }

    public function arg(): string
    {
        return '__toc_' . crc32($this->id());
    }

    protected function generateItems(): array
    {
        $parents = $this->parents;
        $children = $this->page->children($this->edge_types, $this->ignore_sort_order);
        $children->limit(($this->firstPage - $this->perPage) + ($this->page() * $this->perPage));
        $output = [];
        while ($page = $children->fetch()) {
            // skip any pages that are in the parents list
            if (in_array($page->uuid(), $parents)) continue;
            // add list item
            $output[] = sprintf(
                '<li><a href="%s">%s</a>%s</li>',
                $page->url(),
                $page->name(),
                $this->depth > 1 && $page->children($this->edge_types)->count()
                    ? trim(new TableOfContents($page, $this->edge_types, $this->ignore_sort_order, $this->depth - 1, $parents))
                    : ''
            );
        }
        return $output;
    }
}
