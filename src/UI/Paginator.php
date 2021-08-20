<?php

namespace DigraphCMS\UI;

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

class Paginator
{
    protected static $id = 0;
    protected $myID, $count, $perPage, $groupPages;

    public function __construct(int $itemCount, int $perPage = 10, int $groupPages = 10)
    {
        $this->myID = self::$id++;
        $this->count = $itemCount;
        $this->perPage = $perPage;
        $this->groupPages = $groupPages;
    }

    public function __toString()
    {
        if ($this->pages() == 1) {
            return '';
        }
        return "<div class='paginator'>" . implode(' ', $this->links()) . "</div>";
    }

    public function page(): int
    {
        $page = Context::arg($this->arg());
        if ($page < 1 || $page > $this->pages()) {
            Context::unsetArg($this->arg());
            return 1;
        } else {
            return $page;
        }
    }

    public function group(): int
    {
        return floor(($this->page() - 1) / $this->groupPages);
    }

    public function groupCount(): int
    {
        return ceil($this->pages() / $this->groupPages);
    }

    public function groupStartPage(): int
    {
        return ($this->group() * $this->groupPages) + 1;
    }

    public function groupEndPage(): int
    {
        $pages = $this->pages();
        $last = $this->groupStartPage() + $this->groupPages - 1;
        if ($last > $pages) {
            return $pages;
        } else {
            return $last;
        }
    }

    public function startItem(): int
    {
        return ($this->page() - 1) * $this->perPage;
    }

    public function endItem(): int
    {
        return ($this->page() * $this->perPage) - 1;
    }

    protected function links(): array
    {
        $links = [];
        // link to previous page
        if ($this->page() > 1) {
            $links[] = $this->link($this->page()-1,'previous','link-previous');
        }
        // link to first page
        if ($this->group() > 0) {
            $links[] = $this->link(1, 'first', 'link-firstpage');
            // link to previous group
            $links[] = $this->link($this->groupStartPage() - 1, null, 'link-previousgroup');
        }
        // primary page links from within group
        for ($i = $this->groupStartPage(); $i <= $this->groupEndPage(); $i++) {
            $links[] = $this->link($i);
        }
        // link to next group
        if ($this->group() < $this->groupCount() && $this->groupEndPage() < $this->pages()) {
            $links[] = $this->link($this->groupEndPage() + 1, null, 'link-nextgroup');
        }
        // link to last page
        if ($this->group() < $this->groupCount() - 1) {
            $links[] = $this->link($this->pages(), 'last', 'link-lastpage');
        }
        // link to next page
        if ($this->page() < $this->pages()) {
            $links[] = $this->link($this->page()+1,'next','link-next');
        }
        return $links;
    }

    public function link(int $page, string $text = null, string $class = null): string
    {
        $url = $this->url($page);
        $text = $text ?? number_format($page);
        $classes = ['paginator-link'];
        if ($page == $this->page()) {
            $classes[] = 'paginator-link-current';
            $text = "<strong>$text</strong>";
            return "<a data-page='$page' class='" . implode(' ', $classes) . "' title='Page " . number_format($page) . "'>$text</a>";
        }
        if ($class) {
            $classes[] = $class;
        }
        return "<a href='$url' data-page='$page' class='" . implode(' ', $classes) . "' title='Page " . number_format($page) . "'>$text</a>";
    }

    protected function url(int $page): URL
    {
        $url = clone Context::request()->url();
        if ($page == 1) {
            $url->unsetArg($this->arg());
        } else {
            $url->arg($this->arg(), $page);
        }
        return $url;
    }

    public function perPage(int $perPage = null): int
    {
        if ($perPage) {
            $this->perPage = $perPage;
        }
        return $this->perPage;
    }

    public function count(int $count = null): int
    {
        if ($count) {
            $this->count = $count;
        }
        return $this->count;
    }

    public function groupPages(int $groupPages = null): int
    {
        if ($groupPages) {
            $this->groupPages = $groupPages;
        }
        return $this->groupPages;
    }

    public function pages(): int
    {
        return ceil($this->count / $this->perPage);
    }

    public function arg(): string
    {
        return '_page' . ($this->myID ? $this->myID : '');
    }

    public function id(): int
    {
        return $this->myID;
    }
}
