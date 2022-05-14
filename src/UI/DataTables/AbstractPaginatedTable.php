<?php

namespace DigraphCMS\UI\DataTables;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use DigraphCMS\Context;
use DigraphCMS\FS;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Paginator;
use DigraphCMS\URL\URL;

abstract class AbstractPaginatedTable
{
    protected static $id = 0;
    protected $paginator;
    protected $headers = [];
    protected $caption;
    protected $filename;

    abstract public function body(): array;

    public function __construct(int $count)
    {
        $this->myID = self::$id++;
        $this->paginator = new Paginator($count);
    }

    public function setFilename(?string $filename)
    {
        $this->filename = $filename;
        return $this;
    }

    public function caption(string $caption = null): ?string
    {
        if ($caption !== null) {
            $this->caption = $caption;
        }
        return $this->caption;
    }

    public function downloadFile(): File
    {
        return new DeferredFile(
            $this->filename . '.ods',
            function (DeferredFile $file) {
                FS::touch($file->path());
                $writer = WriterEntityFactory::createODSWriter();
                $writer->openToFile($file->path() . '.tmp.ods');
                $writer->close();
                FS::copy($file->path() . '.tmp.ods', $file->path());
                unlink($file->path() . '.tmp.ods');
            },
            'paginatedTable--' . Context::url() . '--' . $this->id()
        );
    }

    public function download(): string
    {
        if (!$this->filename || $this->paginator()->count() == 0) return '';
        ob_start();
        echo "<div class='data-table__download navigation-frame navigation-frame--stateless' id='" . $this->id() . "__download'>";
        $arg = $this->id() . '__download';
        if (Context::arg($arg) == 'true') {
            // prepare download and display link to it
            $file = $this->downloadFile();
            printf(
                '<div class="notification notification--confirmation">Download ready: <a href="%s" target="_top">%s</a></div>',
                $file->url(),
                $file->filename()
            );
        } else {
            // link to initialize
            printf(
                '<a href="%s" class="button">Download table data</a>',
                new URL('&' . $arg . '=true')
            );
        }
        echo "</div>";
        return ob_get_clean();
    }

    public function __toString()
    {
        ob_start();
        echo "<div class='navigation-frame " . $this->class() . "' data-target='_top' id='" . $this->id() . "'>";
        if ($this->paginator()->count() == 0) {
            Notifications::printNotice('Nothing to display');
        } else {
            echo "<div class='data-table__top'>";
            echo $this->paginator();
            echo $this->download();
            echo "</div>";
            echo "<table>";
            if ($this->caption) {
                echo "<caption>" . $this->caption() . "</caption>";
            }
            $this->printHeaders();
            $this->printBody();
            echo "</table>";
            echo "<div class='data-table__bottom'>";
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
            echo "</div>";
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
