<?php

namespace DigraphCMS\Content;

use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DB;
use DigraphCMS\Search\Search;

class Page extends AbstractPage
{

    public function cronJob_index_pages()
    {
        $body = $this->richContent('body');
        if ($body) Search::indexURL($this->uuid(), $this->url(), $this->name(), $body->html());
    }

    public function routeClasses(): array
    {
        return array_unique([$this->class(), 'page', '_any']);
    }
}
