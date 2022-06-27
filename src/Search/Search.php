<?php

namespace DigraphCMS\Search;

use DigraphCMS\DB\DB;
use DigraphCMS\URL\URL;

class Search
{
    public static function indexURL(URL $url, string $title, string $content)
    {
        static::deleteURL($url);
        DB::query()
            ->insertInto(
                'search_index',
                [
                    'url' => $url->fullPathString(),
                    'title' => $title,
                    'body' => static::stripTags($content)
                ]
            )
            ->execute();
    }

    public static function availableModes(): array
    {
        if (true || DB::driver() == 'mysql') {
            return [
                'natural' => 'Natural language',
                'bool' => 'Boolean'
            ];
        }
        return [];
    }

    public static function deleteURL(URL $url)
    {
        DB::query()
            ->delete('search_index')
            ->where('url = ?', [$url->fullPathString()])
            ->execute();
    }

    protected static function stripTags(string $input): string
    {
        return strip_tags($input);
    }
}
