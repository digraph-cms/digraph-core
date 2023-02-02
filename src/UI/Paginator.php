<?php

namespace DigraphCMS\UI;

use DigraphCMS\Context;
use DigraphCMS\HTML\ConditionalContainer;
use DigraphCMS\URL\URL;

class Paginator extends ConditionalContainer
{
    protected static $counter = 0;
    protected $myID, $count, $perPage, $groupPages, $breadcrumbUpdated, $pages;
    /** @var int */
    protected $fudgeFactor = 2;

    public function __construct(?int $itemCount, int $perPage = 10, int $groupPages = 10)
    {
        $this->myID = self::$counter++;
        $this->count = $itemCount;
        $this->perPage = $perPage;
        $this->groupPages = $groupPages;
        $this->addClass('paginator');
    }

    /**
     * Set the "fudge factor" for pagination.
     * 
     * If there are only two pages with the set number of items per page, and
     * fewer than this many items on page two, display will collapse to one page
     * to avoid an aesthetically displeasing situation where adding paginators
     * takes up more room than just including a few more items on page one.
     * 
     * @return static 
     */
    public function setFudgeFactor(int $factor)
    {
        $this->fudgeFactor = $factor;
        return $this;
    }

    /**
     * If there are only two pages with the set number of items per page, and
     * fewer than this many items on page two, display will collapse to one page
     * to avoid an aesthetically displeasing situation where adding paginators
     * takes up more room than just including a few more items on page one.
     * 
     * @return int 
     */
    public function fudgeFactor(): int
    {
        return $this->fudgeFactor;
    }

    public function children(): array
    {
        $children = parent::children();
        if ($this->pages() > 1) {
            foreach ($this->links() as $link) {
                $children[] = $link;
            }
            $children[] = $this->statusDisplay();
        }
        return $children;
    }

    public function __toString()
    {
        $this->updateBreadcrumb();
        return parent::__toString();
    }

    protected function statusDisplay()
    {
        $out = sprintf('%s to %s', number_format($this->startItem() + 1), number_format(min($this->endItem() + 1, $this->count)));
        if ($this->count !== null) $out .= ' of ' . number_format($this->count);
        return sprintf(
            '<span class="paginator__status">%s</span>',
            $out
        );
    }

    protected function updateBreadcrumb()
    {
        if (!$this->breadcrumbUpdated && ($page = $this->page()) > 1) {
            $this->breadcrumbUpdated = true;
            $top = clone Breadcrumb::top();
            $top->setName("Page " . number_format($page) . ' of ' . number_format($this->pages()));
            Breadcrumb::pushParent(clone Breadcrumb::top());
            foreach (Breadcrumb::parents() as $parent) {
                if ($parent->pathString() == $top->pathString()) {
                    $parent->unsetArg($this->arg());
                }
            }
            Breadcrumb::top($top);
        }
    }

    public function page(): int
    {
        $page = Context::arg($this->arg());
        if (!$page || $page < 1) {
            return 1;
        } elseif ($page > $this->pages()) {
            return intval($this->pages());
        } else {
            return $page;
        }
    }

    public function group(): int
    {
        return intval(floor(($this->page() - 1) / $this->groupPages));
    }

    public function groupCount(): float
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
            return intval($pages);
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
            $links[] = $this->link($this->page() - 1, 'previous', 'previous');
        }
        // link to first page
        if ($this->group() > 0) {
            $links[] = $this->link(1, 'first', 'firstpage');
            // link to previous group
            $links[] = $this->link($this->groupStartPage() - 1, null, 'previousgroup');
        }
        // primary page links from within group
        for ($i = $this->groupStartPage(); $i <= $this->groupEndPage(); $i++) {
            $links[] = $this->link($i);
        }
        // link to next group
        if ($this->group() < $this->groupCount() && $this->groupEndPage() < $this->pages()) {
            $links[] = $this->link($this->groupEndPage() + 1, null, 'nextgroup');
        }
        // link to last page
        if ($this->groupCount() !== INF && $this->group() < $this->groupCount() - 1) {
            $links[] = $this->link(intval($this->pages()), 'last', 'lastpage');
        }
        // link to next page
        if ($this->page() < $this->pages()) {
            $links[] = $this->link($this->page() + 1, 'next', 'next');
        }
        return $links;
    }

    public function link(int $page, string $text = null, string $class = null): string
    {
        $url = $this->url($page);
        $text = $text ?? number_format($page);
        $classes = ['paginator__link'];
        if ($page == $this->page()) {
            $classes[] = 'paginator__link--current';
            $text = "<strong>$text</strong>";
            return "<a data-page='$page' class='" . implode(' ', $classes) . "' data-target='_frame' data-navigation-frame-scroll='top' title='Page " . number_format($page) . "'>$text</a>";
        }
        if ($class) {
            $classes[] = 'paginator__link--' . $class;
        }
        return "<a href='$url' data-page='$page' class='" . implode(' ', $classes) . "' data-target='_frame' data-navigation-frame-scroll='top' title='Page " . number_format($page) . "'>$text</a>";
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
        if ($this->count()) {
            if ($this->count() <= $this->perPage + $this->fudgeFactor()) {
                return $this->perPage + $this->fudgeFactor();
            }
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

    public function pages(): float
    {
        if ($this->pages !== null) return $this->pages;
        elseif ($this->count !== null) return ceil($this->count / $this->perPage());
        else return INF;
    }

    public function arg(): string
    {
        return '_page' . ($this->myID ? $this->myID : '');
    }
}
