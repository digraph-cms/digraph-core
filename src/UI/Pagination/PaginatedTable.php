<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\TABLE;
use DigraphCMS\HTML\Tag;

class PaginatedTable extends PaginatedSection
{
    protected $headers;
    protected $tag = 'div';
    protected $dl_button = 'Download table';

    /**
     * @param mixed $source
     * @param callable|null $callback
     * @param array $headers
     */
    public function __construct($source, ?callable $callback, array $headers = [])
    {
        parent::__construct($source, $callback);
        $this->headers = array_map(function ($header) {
            if (!($header instanceof ColumnHeader)) {
                return new ColumnHeader($header);
            }
            return $header;
        }, $headers);
    }

    public function body(): Tag
    {
        if (!$this->body) {
            $items = $this->items();
            if (!$items) return $this->body = (new DIV)->addClass('notification notification--notice')->addChild('Table is empty');
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
                return "<td>$cell</td>";
            },
            call_user_func($this->callback, $cells)
        ));
    }
}
