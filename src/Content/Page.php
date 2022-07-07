<?php

namespace DigraphCMS\Content;

use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DB;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Search\Search;

class Page extends AbstractPage
{

    public function richContent(string $index, RichContent $content = null): ?RichContent
    {
        // update content only if it is different from what exists
        if ($content && !$content->compare($this["content.$index"])) {
            unset($this["content.$index"]);
            $this["content.$index"] = $content->array();
        }
        // return RichContent object
        if ($this["content.$index"]) {
            return new RichContent($this["content.$index"]);
        } else {
            return null;
        }
    }

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

    public function allRichContent(): array
    {
        return array_map(
            function ($arr) {
                return new RichContent($arr);
            },
            $this['content'] ?? []
        );
    }

    public function routeClasses(): array
    {
        return ['page', '_any'];
    }
}
