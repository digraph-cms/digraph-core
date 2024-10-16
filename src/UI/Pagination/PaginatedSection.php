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
use DigraphCMS\UI\Paginator;
use DigraphCMS\URL\URL;
use Envms\FluentPDO\Queries\Select;
use Iterator;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class PaginatedSection extends Tag
{
    protected static $counter = 0;
    protected $paginator = false;
    protected $count;
    protected $tag = 'div';
    protected $source, $filteredSource;
    protected $body, $top, $bottom, $before, $after;
    protected $items;
    protected $callback;
    protected $dl_filename, $dl_callback, $dl_headers, $dl_finalize_callback, $dl_ttl;
    protected $dl_button = 'Download';
    protected $dl_permissions;
    /** @var FilterToolInterface[] */
    protected $filterTools = [];

    /**
     * @param mixed $source
     * @param callable|null $callback
     */
    public function __construct($source, ?callable $callback)
    {
        $this->setId('p' . crc32(get_called_class()) . '--' . static::$counter++);
        $this->addClass('paginated-section');
        $this->addClass('navigation-frame');
        $this->setData('target', '_top');
        $this->source = $source;
        $this->callback = $callback ?? function ($item) {
            return $item;
        };
    }

    /**
     * Add a filter tool, which can define new order/where clauses for queries.
     * Filter tools are automatically passed their parent paginated section so
     * that they can use it to get config and URLs.
     *
     * @param FilterToolInterface $tool
     * @return static
     */
    public function addFilterTool(FilterToolInterface $tool)
    {
        $this->filterTools[$tool->getFilterID()] = $tool;
        $tool->setSection($this);
        $this->filteredSource = null;
        return $this;
    }

    public function url(string $tool, $config): URL
    {
        $fullConfig = $this->getFilterConfig();
        unset($fullConfig[$tool]);
        if ($config !== null) $fullConfig[$tool] = $config;
        $finalConfig = [];
        foreach ($fullConfig as $tool => $config) {
            // I know it feels weird to store these with key/value as a tuple,
            // but it's important because it preserves the order the filters
            // have been applied in by the user, if there are more than one
            $finalConfig[] = [$tool, $config];
        }
        $url = Context::url();
        if ($finalConfig) $url->arg('filter_' . $this->id(), json_encode($finalConfig));
        else $url->unsetArg('filter_' . $this->id());
        return $url;
    }

    public function paginator(): Paginator
    {
        if ($this->paginator === false) {
            if ($this->source() instanceof Countable) $this->count = $this->source()->count();
            elseif (is_array($this->source())) $this->count = count($this->source());
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

    protected function getFilterConfig(): array
    {
        if (!$this->filterTools) return [];
        if ($arg = Context::arg('filter_' . $this->id())) {
            // get and decode arg
            $json = json_decode($arg, true);
            if (!is_array($json)) return [];
            // convert into key/value array
            $output = [];
            foreach ($json as list($tool, $config)) {
                if (isset($this->filterTools[$tool])) $output[$tool] = $config;
            }
            // return output
            return $output;
        } else {
            return [];
        }
    }

    public function getToolConfig(string $tool)
    {
        return @$this->getFilterConfig()[$tool];
    }

    public function tableName(): ?string
    {
        if (
            $this->source instanceof Select
            || $this->source instanceof AbstractMappedSelect
        ) {
            return $this->source->getFromTable();
        } else return null;
    }

    public function rawSource()
    {
        return $this->source;
    }

    public function source()
    {
        if ($this->filteredSource !== null) return $this->filteredSource;
        elseif (
            $this->source instanceof Select
            || $this->source instanceof AbstractMappedSelect
            || (is_object($this->source)
                && method_exists($this->source, 'where')
                && method_exists($this->source, 'order')
                && method_exists($this->source, 'leftJoin'))
        ) {
            // clone source and do basic sorting and filtering
            /** @var AbstractMappedSelect */
            $source = clone $this->source;
            // apply filter tools
            $join = [];
            $where = [];
            $like = [];
            $order = [];
            foreach (array_keys(array_reverse($this->getFilterConfig())) as $tool) {
                $join = array_merge($join, $this->filterTools[$tool]->getJoinClauses());
                $where = array_merge($where, $this->filterTools[$tool]->getWhereClauses());
                $like = array_merge($like, $this->filterTools[$tool]->getLikeClauses());
                $order = array_merge($order, $this->filterTools[$tool]->getOrderClauses());
            }
            foreach ($join as $clause) {
                $source->leftJoin($clause);
            }
            if (\method_exists($source, 'like')) {
                foreach ($like as list($column, $pattern)) {
                    $source->like($column, $pattern);
                }
            } else {
                foreach ($like as list($column, $pattern)) {
                    $source->where(AbstractMappedSelect::parseJsonRefs($column) . " LIKE ?", AbstractMappedSelect::prepareLikePattern($pattern));
                }
            }
            foreach ($where as list($clause, $args)) {
                $source->where($clause, $args);
            }
            if ($order) $source->order(null);
            foreach ($order as $clause) {
                $source->order($clause);
            }
            return $this->filteredSource = $source;
        } else return $this->filteredSource = $this->source;
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
                $this->source() instanceof Select
                || $this->source() instanceof AbstractMappedSelect
                || (is_object($this->source())
                    && method_exists($this->source(), 'offset')
                    && method_exists($this->source(), 'limit')
                    && method_exists($this->source(), 'fetchAll'))
            ) {
                // turn filtered/paginated results into array for final displaying
                $source = clone $this->source();
                if ($this->paginator()->startItem()) $source->offset($this->paginator()->startItem());
                $source->limit($this->paginator()->perPage());
                $this->items = $source->fetchAll();
            }
            // Use built-in array_slice to cut out the requested section of an array
            elseif (is_array($this->source())) {
                $this->items = array_slice(
                    $this->source(),
                    $this->paginator()->startItem(),
                    $this->paginator()->perPage()
                );
            }
            // Straight Iterators aren't especially efficient, because you have to iterate through 
            // everything prior to the first item 
            elseif ($this->source() instanceof Iterator) {
                $source = clone $this->source();
                $this->source()->rewind();
                // skip first items
                for ($i = 1; $i < $this->paginator()->startItem(); $i++) $source->next();
                // grab perpage items
                $this->items = [];
                for ($i = 0; $i < $this->paginator()->perPage(); $i++) {
                    $this->items[] = $source->current() ?? false;
                    $source->next();
                }
            }
            // Try to hand off to Dispatcher so that we can extend this
            elseif ($this->items = Dispatcher::firstValue('onPaginatedList', [$this->source()])) {
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
            if (!$items) return $this->body = (new DIV)->addClass('notification notification--notice')->addChild('Section is empty');
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
                '<a href="%s" class="button" target="_top" download="%s" id="%s" rel="nofollow">%s</a>',
                $file->url(),
                $file->filename(),
                $arg,
                $file->filename()
            );
            // auto download
            $out .= sprintf('<script>document.getElementById("%s").click();</script>', $arg);
        } else {
            // link to initialize
            $out .= sprintf(
                '<a href="%s" class="button" rel="nofollow">%s%s</a>',
                new URL('&' . $arg . '=true'),
                $this->dl_button,
                $this->getFilterConfig() ? ' (filtered)' : '',
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
     * @return static
     */
    public function download(
        string $filename,
        callable $callback,
        array $headers = [],
        callable $finalizeCallback = null,
        string $buttonText = null,
        int $ttl = null,
        callable|null $permissions = null,
    ) {
        $this->dl_filename = preg_replace('/[^a-z0-9 _\-]+/i', '_', $filename);
        $this->dl_callback = $callback;
        $this->dl_headers = $headers;
        $this->dl_finalize_callback = $finalizeCallback;
        $this->dl_button = $buttonText ?? $this->dl_button;
        $this->dl_ttl = $ttl;
        $this->dl_permissions = $permissions;
        return $this;
    }

    protected function downloadFile(): File
    {
        return new DeferredFile(
            $this->dl_filename . ($this->getFilterConfig() ? ' (filtered)' : '') . '.xlsx',
            function (DeferredFile $file) {
                FS::touch($file->path());
                $writer = new SpreadsheetWriter();
                // write headers
                if ($this->dl_headers) $writer->writeHeaders($this->dl_headers);
                // loop through source and run callback to get cells
                $source = $this->source();
                if (
                    $source instanceof Select
                    || $source instanceof AbstractMappedSelect
                    || (is_object($source)
                        && method_exists($source, 'fetch'))
                ) {
                    $source = clone $source;
                    while ($item = $source->fetch()) {
                        $writer->writeRow($this->runDlCallback($item));
                    }
                } else {
                    foreach ($source as $item) {
                        if (!$item) continue;
                        $writer->writeRow($this->runDlCallback($item));
                    }
                }
                // run finalization callback
                if ($this->dl_finalize_callback) call_user_func($this->dl_finalize_callback, $writer);
                // save file
                (new Xlsx($writer->spreadsheet()))
                    ->save($file->path() . '.tmp');
                FS::copy($file->path() . '.tmp', $file->path());
                unlink($file->path() . '.tmp');
            },
            [get_called_class(), $this->downloadFileID()],
            $this->dl_ttl,
            $this->dl_permissions
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
            $this->id(),
            $this->getFilterConfig()
        ]));
    }
}
