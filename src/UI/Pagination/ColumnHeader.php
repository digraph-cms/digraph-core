<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\Context;
use DigraphCMS\UI\Breadcrumb;

class ColumnHeader
{
    protected static $id = 0;
    protected $myID, $label, $sorter, $order;

    public function __construct(string $label, callable $sorter = null)
    {
        $this->myID = self::$id++;
        $this->label = $label;
        $this->sorter = $sorter;
        // set up sorting state
        $order = Context::url()->arg('_sortorder');
        $column = Context::url()->arg('_sortcolumn');
        if ($column == $this->id()) {
            if ($order == 'asc') {
                $this->order = 'asc';
                $this->updateBreadcrumb($this->label, 'ascending');
            } elseif ($order == 'desc') {
                $this->order = 'desc';
                $this->updateBreadcrumb($this->label, 'descending');
            } else {
                Context::url()->unsetArg('_sortorder');
                Context::url()->unsetArg('_sortcolumn');
            }
        }
    }

    protected function classes(): array
    {
        $classes = [
            $this->order ? 'sorted' : 'unsorted'
        ];
        if ($this->order) {
            $classes[] = 'sorted-' . $this->order;
        }
        if ($this->sorter) {
            $classes[] = 'sortable';
        }
        return $classes;
    }

    public function colString(): string
    {
        return "<col class='" . implode(' ', $this->classes()) . "'></col>";
    }

    protected function updateBreadcrumb(string $label, string $order)
    {
        $top = clone Breadcrumb::top();
        $top->setName("Sorted by: $label $order");
        Breadcrumb::pushParent(clone Breadcrumb::top());
        foreach (Breadcrumb::parents() as $parent) {
            if ($parent->pathString() == $top->pathString()) {
                $parent->unsetArg('_sortorder');
                $parent->unsetArg('_sortcolumn');
            }
        }
        Breadcrumb::top($top);
    }

    public function __toString()
    {
        // set up sorting
        if ($this->sorter) {
            // is sortable
            if ($this->order == 'asc') {
                ($this->sorter)(true);
            } elseif ($this->order == 'desc') {
                ($this->sorter)(false);
            }
        }
        // output
        ob_start();
        echo "<th class='" . implode(' ', $this->classes()) . "'>";
        echo $this->label;
        $this->printSorter();
        echo "</th>";
        $return = ob_get_contents();
        ob_end_clean();
        return $return;
    }

    protected function printSorter()
    {
        if (!$this->sorter) {
            return;
        }
        echo "<span class='column-sorter'>";
        echo $this->link('asc', '[a..z]', 'Sort ascending');
        echo $this->link('desc', '[z..a]', 'Sort descending');
        echo $this->link(null, '[x]', 'Clear sort');
        echo "</span>";
    }

    protected function link($order, $text, $tip): string
    {
        $classes = ['column-sort'];
        $url = clone Context::url();
        if ($order) {
            $url->arg('_sortorder', $order);
            $url->arg('_sortcolumn', $this->id());
            $classes[] = 'column-sort-' . $order;
        } elseif ($this->id() == $url->arg('_sortcolumn') && in_array($url->arg('_sortorder'), ['asc', 'desc'])) {
            $url->unsetArg('_sortorder');
            $url->unsetArg('_sortcolumn');
            $classes[] = 'column-sort-clear';
        } else {
            // this is a reset link for a column that isn't sorted, return nothing
            return '';
        }
        if (Context::url()->arg('_sortorder') == $order && Context::url()->arg('_sortcolumn') == $this->id()) {
            // this is a link to the current sorting, return a link that isn't href-ed
            return "<a title='$tip' aria-label='$tip' data-target='_frame' class='" . implode(' ', $classes) . " column-sort-active'>$text</a>";
        }
        return "<a href='$url' title='$tip' aria-label='$tip' data-target='_frame' class='" . implode(' ', $classes) . "'>$text</a>";
    }

    public function id(): string
    {
        return 'c' . $this->myID;
    }
}
