<?php

namespace DigraphCMS\Search;

use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\URL\URL;

class Search
{
    public static function indexURL(URL $url, string $title, string $content)
    {
        if (DB::query()->from('search_index')->where('url = ?', [$url->fullPathString()])->count()) {
            // update existing record if it exists
            DB::query()
                ->update(
                    'search_index',
                    [
                        'title' => $title,
                        'body' => static::cleanBody($content)
                    ]
                )
                ->where('url=?', [$url->fullPathString()])
                ->execute();
        } else {
            // otherwise insert a fresh record
            DB::query()
                ->insertInto(
                    'search_index',
                    [
                        'url' => $url->fullPathString(),
                        'title' => $title,
                        'body' => static::cleanBody($content)
                    ]
                )
                ->execute();
        }
    }

    public static function query(string $search, string $mode = null): AbstractSearchQuery
    {
        switch ($mode) {
            case 'natural':
                $query = new NaturalSearchQuery($search);
                break;
            case 'boolean':
                $query = new BooleanSearchQuery($search);
                break;
            default:
                $query = new CompatibleSearchQuery($search);
        }
        Dispatcher::dispatchEvent('onSearchQuery', [$query, $search, $mode]);
        return $query;
    }

    public static function availableModes(): array
    {
        if (DB::driver() == 'mysql') {
            return [
                'natural' => 'Natural language',
                'boolean' => 'Boolean'
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

    protected static function cleanBody(string $input): string
    {
        return strtolower(strip_tags($input));
    }
}
