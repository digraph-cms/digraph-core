<?php

namespace DigraphCMS\UI\DataTables;

use DigraphCMS\Context;
use DigraphCMS\FS;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Paginator;
use DigraphCMS\URL\URL;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use function Opis\Closure\serialize;

abstract class AbstractPaginatedTable
{
    protected static $id = 0;
    protected $myID;
    protected $paginator;
    protected $headers = [];
    protected $caption;
    protected $downloadFilename;
    protected $downloadCallback;
    protected $downloadHeaders;

    abstract public function body(): array;

    public function __construct(int $count)
    {
        $this->myID = self::$id++;
        $this->paginator = new Paginator($count);
    }

    /**
     * Set headers
     *
     * @param ColumnHeader[]|string[] $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_map(function ($header) {
            if (!($header instanceof ColumnHeader)) {
                return new ColumnHeader($header);
            }
            return $header;
        }, $headers);
        return $this;
    }

    public function enableDownload(string $filename, callable $callback, array $headers)
    {
        $this->downloadFilename = $filename;
        $this->downloadCallback = $callback;
        $this->downloadHeaders = $headers;
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
            $this->downloadFilename . '.xlsx',
            function (DeferredFile $file) {
                FS::touch($file->path());
                $spreadsheet = new Spreadsheet();
                $writer = new DownloadWriter($spreadsheet);
                // set up headers
                $writer->writeHeaders($this->downloadHeaders);
                // call abstract method to convert data into rows
                $this->writeDownloadFile($writer);
                // wrap up formatting stuff
                $spreadsheet->setActiveSheetIndex(0);
                $spreadsheet->getActiveSheet()->setAutoFilter(
                    $spreadsheet->getActiveSheet()->calculateWorksheetDimension()
                );
                // save file
                (new Xlsx($spreadsheet))
                    ->save($file->path() . '.tmp');
                FS::copy($file->path() . '.tmp', $file->path());
                unlink($file->path() . '.tmp');
            },
            'tabledownload/' . $this->downloadFileID()
        );
    }

    protected function downloadFileID(): string
    {
        return md5(serialize([
            $this,
            Context::url(),
            Context::request()->post()
        ]));
    }

    abstract protected function writeDownloadFile(DownloadWriter $writer);

    public function download(): string
    {
        if (!$this->downloadFilename || $this->paginator()->count() == 0) return '';
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
