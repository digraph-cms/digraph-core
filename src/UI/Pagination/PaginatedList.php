<?php

namespace DigraphCMS\UI\Pagination;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\LI;
use DigraphCMS\HTML\Tag;
use DigraphCMS\HTML\UL;

class PaginatedList extends PaginatedSection
{
    protected $tag = 'div';
    protected $dl_button = 'Download all';

    public function body(): Tag
    {
        if (!$this->body) {
            $items = $this->items();
            if (!$items) return $this->body = (new DIV)->addClass('notification notification--notice')->addChild('List is empty');
            $this->body = new UL;
            $this->body->addClass('paginated-section__body');
            foreach ($items as $item) {
                $this->body->addChild($item);
            }
        }
        return $this->body;
    }

    protected function runCallback($item)
    {
        return (new LI)->addChild(call_user_func($this->callback, $item));
    }
}
