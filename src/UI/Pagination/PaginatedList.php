<?php

namespace DigraphCMS\UI\Pagination;

use Countable;
use DigraphCMS\Context;
use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\FS;
use DigraphCMS\HTML\ConditionalContainer;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Tag;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;
use DigraphCMS\Spreadsheets\SpreadsheetWriter;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Paginator;
use DigraphCMS\URL\URL;
use Envms\FluentPDO\Queries\Select;
use Iterator;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class PaginatedList extends Tag
{
    protected static $counter = 0;
    protected $paginator = false;
    protected $count;
    protected $tag = 'div';
    protected $source;
    protected $body, $top, $bottom, $before, $after;
    protected $items;
    protected $callback;
    protected $dl_filename, $dl_callback, $dl_headers, $dl_finalize_callback;

    /**
     * @param mixed $source
     * @param callable|null $callback
     */
    public function __construct($source, ?callable $callback)
    {
        $this->setId(md5(get_called_class()) . '--' . static::$counter++);
        $this->addClass('paginated-section');
        $this->addClass('navigation-frame');
        $this->setData('target', '_top');
        $this->source = $source;
        $this->callback = $callback ?? function ($item) {
            return $item;
        };
    }

    public function paginator(): Paginator
    {
        if ($this->paginator === false) {
            if ($this->source instanceof Countable) $this->count = $this->source->count();
            elseif (is_array($this->source)) $this->count = count($this->source);
            $this->paginator = new Paginator($this->count);
        }
        return $this->paginator;
    }

    public function children(): array
    {
        $children = [];
        $children[] = $this->top();
        $children[] = $this->before();
        $children[] = $this->body();
        $children[] = $this->after();
        $children[] = $this->bottom();
        return $children;
    }

    /**
     * Get the items that should be added to the body, which are generated by
     * applying the callback to the necessary items
     *
     * @return array
     */
    protected function items(): array
    {
        if ($this->items === null) {
            if (
                $this->source instanceof Select
                || $this->source instanceof AbstractMappedSelect
                || (is_object($this->source) && method_exists($this->source, 'offset') && method_exists($this->source, 'limit') && method_exists($this->source, 'fetchAll'))
            ) {
                $source = clone $this->source;
                $source->offset($this->paginator()->startItem());
                $source->limit($this->paginator()->perPage());
                $this->items = $source->fetchAll();
            }
            // Use built-in array_slice to cut out the requested section of an array
            elseif (is_array($this->source)) {
                $this->items = array_slice(
                    $this->source,
                    $this->paginator()->startItem(),
                    $this->paginator()->endItem() - $this->paginator()->startItem()
                );
            }
            // Straight Iterators aren't especially efficient, because you have to iterate through 
            // everything prior to the first item 
            elseif ($this->source instanceof Iterator) {
                $source = clone $this->source;
                $this->source->rewind();
            }
            // Try to hand off to Dispatcher so that we can extend this
            elseif ($this->items = Dispatcher::firstValue('onPaginatedList', [$this->source])) {
                // does nothing, assignment happened in the elseif statement
            }
            // Throw an exception if we don't know how to handle this source
            else {
                throw new \Exception("Unable to paginate source");
            }
            // map callback onto items
            $this->items = array_filter(array_map(
                function ($item) {
                    return $this->runCallback($item);
                },
                $this->items
            ));
        }
        // return generated items list
        return $this->items;
    }

    protected function runCallback($item)
    {
        return call_user_func($this->callback, $item) ?? false;
    }

    public function before(): ConditionalContainer
    {
        if (!$this->before) {
            $this->before = new ConditionalContainer;
            // add paginator
            $this->before->addClass('paginated-section__before');
            $this->before->addChild($this->paginator());
            // add spacer to keep everything after paginator right
            $this->before->addChild('<span class="paginated-section__spacer"></span>');
            // add download if necessary
            if ($this->dl_filename && $this->paginator()->count() > 0) {
                $this->before->addChild($this->downloadTool());
            }
        }
        return $this->before;
    }

    public function after(): ConditionalContainer
    {
        if (!$this->after) {
            // add paginator
            $this->after = new ConditionalContainer;
            $this->after->addClass('paginated-section__after');
            $this->after->addChild($this->paginator());
            // add spacer to keep everything after paginator right
            $this->after->addChild('<span class="paginated-section__spacer"></span>');
        }
        return $this->after;
    }

    public function top(): ConditionalContainer
    {
        if (!$this->top) {
            $this->top = new ConditionalContainer;
            $this->top->addClass('paginated-section__top');
        }
        return $this->top;
    }

    public function bottom(): ConditionalContainer
    {
        if (!$this->bottom) {
            $this->bottom = new ConditionalContainer;
            $this->bottom->addClass('paginated-section__bottom');
        }
        return $this->bottom;
    }

    public function body(): Tag
    {
        if (!$this->body) {
            $items = $this->items();
            if (!$items) return $this->body = (new DIV)->addClass('notification notification--notice')->addChild('Nothing to display');
            $this->body = new DIV;
            $this->body->addClass('paginated-section__body');
            foreach ($items as $item) {
                $this->body->addChild($item);
            }
        }
        return $this->body;
    }

    public function downloadTool(): string
    {
        $out = "<div class='paginated-section__download navigation-frame navigation-frame--stateless' id='" . $this->id() . "__download'>";
        $arg = 'dl_' . md5($this->id());
        if (Context::arg($arg) == 'true') {
            // prepare download and display link to it
            $file = $this->downloadFile();
            $out .= sprintf(
                '<div class="notification notification--confirmation">Ready: <a href="%s" target="_top">%s</a></div>',
                $file->url(),
                $file->filename()
            );
        } else {
            // link to initialize
            $out .= sprintf(
                '<a href="%s" class="button">Download</a>',
                new URL('&' . $arg . '=true')
            );
        }
        $out .= "</div>";
        return $out;
    }

    /**
     * @param string $filename
     * @param callable $callback
     * @param array $headers
     * @param callable|null $finalizeCallback
     * @return $this
     */
    public function download(string $filename, callable $callback, array $headers = [], callable $finalizeCallback = null)
    {
        $this->dl_filename = $filename;
        $this->dl_callback = $callback;
        $this->dl_headers = $headers;
        $this->dl_finalize_callback = $finalizeCallback;
        return $this;
    }

    protected function downloadFile(): File
    {
        return new DeferredFile(
            $this->dl_filename . '.xlsx',
            function (DeferredFile $file) {
                FS::touch($file->path());
                $writer = new SpreadsheetWriter();
                // write headers
                if ($this->dl_headers) $writer->writeHeaders($this->dl_headers);
                // loop through source and run callback to get cells
                foreach ($this->source as $item) {
                    $writer->writeRow($this->runDlCallback($item));
                }
                // run finalization callback
                if ($this->finalizeCallback) call_user_func($this->finalizeCallback, $writer);
                // save file
                (new Xlsx($writer->spreadsheet()))
                    ->save($file->path() . '.tmp');
                FS::copy($file->path() . '.tmp', $file->path());
                unlink($file->path() . '.tmp');
            },
            [get_called_class(), $this->downloadFileID()]
        );
    }

    protected function runDlCallback($item): array
    {
        return call_user_func($this->dl_callback, $item);
    }

    protected function downloadFileID(): string
    {
        return md5(serialize([
            Context::url()->path(),
            $this->id()
        ]));
    }
}
