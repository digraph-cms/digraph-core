<?php

namespace DigraphCMS\UI\DataLists;

use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Paginator;

abstract class AbstractPaginatedList
{
    protected static $id = 0;
    protected $paginator;
    protected $caption;

    abstract public function items(): array;

    public function __construct(int $count)
    {
        $this->myID = self::$id++;
        $this->paginator = new Paginator($count);
    }

    public function caption(string $caption = null): ?string
    {
        if ($caption !== null) {
            $this->caption = $caption;
        }
        return $this->caption;
    }

    public function __toString()
    {
        ob_start();
        echo "<figure class='navigation-frame " . $this->class() . "' data-target='_top' id='" . $this->id() . "'>";
        if ($this->caption) {
            echo "<caption>" . $this->caption() . "</caption>";
        }
        if ($this->paginator()->count() == 0) {
            Notifications::printNotice('Nothing to display');
        } else {
            echo $this->paginator();
            echo "<ul>";
            $this->printBody();
            echo "</ul>";
            echo $this->paginator();
            if ($this->paginator()->pages() > 1) {
                $start = number_format($this->paginator->startItem() + 1);
                $end = $this->paginator->endItem() + 1;
                if ($end > $this->paginator->count()) {
                    $end = $this->paginator->count();
                }
                $end = number_format($end);
                $count = number_format($this->paginator->count());
                echo "<small class='paginator-status'>Displaying $start to $end of $count</small>";
            }
        }
        echo "</figure>";
        return ob_get_clean();
    }

    public function paginator(): Paginator
    {
        return $this->paginator;
    }

    public function printBody()
    {
        foreach ($this->items() as $item) {
            echo '<li>';
            echo $item;
            echo '</li>';
        }
    }

    public function class(): string
    {
        return 'data-list';
    }

    public function arg(string $name): string
    {
        return $this->id() . '_' . $name;
    }

    public function id(): string
    {
        return '_datalist' . $this->myID;
    }
}
