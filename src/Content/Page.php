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

    public static function onRecursiveDelete(DeferredJob $job, AbstractPage $page)
    {
        $uuid = $page->uuid();
        $job->spawn(function () use ($uuid) {
            $n = DB::query()
                ->delete('search_index')
                ->where('owner = ?', [$uuid])
                ->execute();
            return "Deleted search indexes created by page $uuid ($n)";
        });
    }

    public function routeClasses(): array
    {
        return ['page', '_any'];
    }
}
