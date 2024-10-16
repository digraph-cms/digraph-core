<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\TABLE;
use DigraphCMS\HTML\Tag;

class PaginatedTable extends PaginatedSection
{
    protected $headers;
    protected $tag = 'div';
    protected $dl_button = 'Download data';

    /**
     * @param mixed $source
     * @param callable|null $callback
     * @param array|null $headers
     */
    public function __construct($source, ?callable $callback = null, array|null $headers = null)
    {
        if (!$callback) $callback = function (array $row): array {
            return $row;
        };
        parent::__construct($source, $callback);
        $this->headers = array_map(function ($header) {
            if (!($header instanceof ColumnHeader)) return new ColumnHeader($header);
            if ($header instanceof FilterToolInterface) $this->addFilterTool($header);
            return $header;
        }, $headers ?? []);
    }

    public function download(
        string $filename,
        callable|null $callback,
        array|null $headers = [],
        ?callable $finalizeCallback = null,
        ?string $buttonText = null,
        ?int $ttl = null,
        ?callable $permissions = null
    ) {
        return parent::download(
            $filename,
            $callback ?? function (mixed $input): array {
                $row = call_user_func($this->callback, $input);
                $row = array_map(
                    function ($cell) {
                        $cell = strval($cell);
                        $cell = strip_tags($cell, ['<br>']);
                        $cell = str_ireplace('<br>', PHP_EOL, $cell);
                        return $cell;
                    },
                    $row
                );
                return $row;
            },
            $headers ?? array_map(fn (ColumnHeader $h) => $h->label(), $this->headers),
            $finalizeCallback,
            $buttonText,
            $ttl,
            $permissions
        );
    }

    public function body(): Tag
    {
        if (!$this->body) {
            // load items
            $items = $this->items();
            // prepare body wrapper and headers
            $this->body = new TABLE;
            $this->body->addClass('paginated-section__body');
            if ($this->headers) {
                $this->body->addChild('<colgroup>' . implode(PHP_EOL, array_map(
                    function (ColumnHeader $header) {
                        return $header->colString();
                    },
                    $this->headers
                )) . '</colgroup>');
                $this->body->addChild('<tr>' . implode(PHP_EOL, $this->headers) . '</tr>');
            }
            // insert items
            if (!$items) $this->body->addChild(sprintf(
                '<tr class="paginated-table__noresults"><td colspan="%s">Nothing to display</td></tr>',
                $this->headers ? count($this->headers) : 1
            ));
            foreach ($items as $item) {
                $this->body->addChild('<tr>' . $item . '</tr>');
            }
        }
        return $this->body;
    }

    protected function runCallback($cells)
    {
        return implode(PHP_EOL, array_map(
            function ($cell) {
                if (is_array($cell)) {
                    array_walk($cell, function (&$value, $key) {
                        if (!$value) $value = false;
                        else $value = "<div><strong>$key</strong>: $value</div>";
                    });
                    return '<td class="paginated-table__autoarray">' . implode(PHP_EOL, array_filter($cell)) . '</td>';
                }
                return "<td>$cell</td>";
            },
            call_user_func($this->callback, $cells)
        ));
    }
}
