<?php

namespace DigraphCMS\Content;

use DigraphCMS\RichContent\RichContent;

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
