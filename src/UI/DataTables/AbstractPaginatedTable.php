<?php

namespace DigraphCMS\UI\DataTables;

use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Paginator;

abstract class AbstractPaginatedTable
{
    protected static $id = 0;
    protected $paginator;
    protected $headers = [];
    protected $caption;

    abstract public function body(): array;

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
        echo "<div class='navigation-frame " . $this->class() . "' id='" . $this->id() . "'>";
        if ($this->paginator()->count() == 0) {
            Notifications::printNotice('Nothing to display');
        } else {
            echo $this->paginator();
            echo "<table>";
            if ($this->caption) {
                echo "<caption>" . $this->caption() . "</caption>";
            }
            $this->printHeaders();
            $this->printBody();
            echo "</table>";
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
        echo "</div>";
        return ob_get_clean();
    }

    public function printHeaders()
    {
        if (!$this->headers) {
            return;
        }
        echo "<colgroup>";
        foreach ($this->headers as $header) {
            echo $header->colString();
        }
        echo "</colgroup>";
        echo "<tr>";
        foreach ($this->headers as $header) {
            echo $header;
        }
        echo "</tr>";
    }

    public function paginator(): Paginator
    {
        return $this->paginator;
    }

    public function printBody()
    {
        foreach ($this->body() as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo "<td>$cell</td>";
            }
            echo '</tr>';
        }
    }

    public function class(): string
    {
        return 'data-table';
    }

    public function arg(string $name): string
    {
        return $this->id() . '_' . $name;
    }

    public function id(): string
    {
        return '_datatable' . $this->myID;
    }
}
