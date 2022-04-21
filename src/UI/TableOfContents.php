<?php

namespace DigraphCMS\UI;

use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;

class TableOfContents extends Tag
{
    protected $tag = 'ul';
    protected $page;
    protected $firstPage = 100;
    protected $perPage = 10;
    protected $sort = 'name ASC';

    public function __construct(Page $page)
    {
        $this->page = $page;
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
        $count = Pages::children($this->page->uuid())->count();
        if ($count <= $this->firstPage) return 1;
        else return ceil(($count - $this->firstPage) / $this->perPage) + 1;
    }

    public function generateMoreLink(): string
    {
        $url = Context::url();
        $url->arg($this->arg(), $this->page() + 1);
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
        $children = Pages::children($this->page->uuid(), $this->sort);
        $children->limit(($this->firstPage - $this->perPage) + ($this->page() * $this->perPage));
        $output = [];
        while ($page = $children->fetch()) $output[] = sprintf(
            '<li><a href="%s">%s</a>%s</li>',
            $page->url(),
            $page->name(),
            (Pages::children($page->uuid())->count() ? trim(new TableOfContents($page)) : '')
        );
        return $output;
    }
}
