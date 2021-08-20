<?php

namespace DigraphCMS\UI\DataTables;

use DigraphCMS\Context;

class ColumnHeader
{
    protected static $id = 0;
    protected $myID, $label, $sorter, $order;

    public function __construct(string $label, callable $sorter = null)
    {
        $this->myID = self::$id++;
        $this->label = $label;
        $this->sorter = $sorter;
        if ($this->sorter) {
            // is sortable
            $order = Context::arg('_sortorder');
            $column = Context::arg('_sortcolumn');
            if ($column == $this->id()) {
                if ($order == 'asc') {
                    $this->order = 'asc';
                    ($this->sorter)(true);
                } elseif ($order == 'desc') {
                    $this->order = 'desc';
                    ($this->sorter)(false);
                }
            }
        } else {
            // is not sortable
            Context::unsetArg($this->id());
        }
    }

    public function __toString()
    {
        ob_start();
        echo "<th>";
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
        echo "<sup class='column-sorter inline-button-group'>";
        echo $this->link('asc', '[a..z]', 'Sort ascending');
        echo $this->link('desc', '[z..a]', 'Sort descending');
        echo $this->link(null, '[x]', 'Clear sort');
        echo "</sup>";
    }

    protected function link($order, $text, $tip): string
    {
        $classes = ['column-sort'];
        $url = Context::url();
        if ($order) {
            $url->arg('_sortorder', $order);
            $url->arg('_sortcolumn', $this->id());
            $classes[] = 'column-sort-' . $order;
        } elseif ($this->id() == $url->arg('_sortcolumn')) {
            $url->unsetArg('_sortorder');
            $url->unsetArg('_sortcolumn');
            $classes[] = 'column-sort-clear';
        }else {
            // this is a reset link for a column that isn't sorted, return nothing
            return '';
        }
        if (Context::arg('_sortorder') == $order && Context::arg('_sortcolumn') == $this->id()) {
            // this is a link to the current sorting, return a link that isn't href-ed
            return "<a title='$tip' class='" . implode(' ', $classes) . " column-sort-active'>$text</a>";
        }
        return "<a href='$url' title='$tip' class='" . implode(' ', $classes) . "'>$text</a>";
    }

    public function id(): string
    {
        return 'c'.$this->myID;
    }
}
