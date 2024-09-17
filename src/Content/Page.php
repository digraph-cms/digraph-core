<?php

namespace DigraphCMS\Content;

use DigraphCMS\Context;
use DigraphCMS\Search\Search;

class Page extends AbstractPage
{

    public function cronJob_index_pages()
    {
        $body = $this->richContent('body');
        if ($body) {
            Context::beginPageContext($this);
            Search::indexURL($this->uuid(), $this->url(), $this->name(), $body->html());
            Context::end();
        }
    }

    public function routeClasses(): array
    {
        return array_unique([$this->class(), 'page', '_any']);
    }
}
