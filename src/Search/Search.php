<?php

namespace DigraphCMS\Search;

use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\URL\URL;

class Search
{
    public static function indexURL(string $owner, URL $url, string $title, string $content)
    {
        $title = strip_tags($title);
        if (DB::query()->from('search_index')->where('url = ?', [$url->fullPathString()])->count()) {
            // update existing record if it exists
            DB::query()
                ->update(
                    'search_index',
                    [
                        'owner' => $owner,
                        'title' => $title,
                        'body' => static::cleanBody($content),
                        'updated' => time()
                    ]
                )
                ->where('url = ?', [$url->fullPathString()])
                ->execute();
        } else {
            // otherwise insert a fresh record
            DB::query()
                ->insertInto(
                    'search_index',
                    [
                        'owner' => $owner,
                        'url' => $url->fullPathString(),
                        'title' => $title,
                        'body' => static::cleanBody($content),
                        'updated' => time()
                    ]
                )
                ->execute();
        }
    }

    public static function query(string $search): AbstractSearchQuery
    {
        switch (DB::driver() == 'mysql') {
            case 'value':
                $query = new NaturalSearchQuery($search);
                break;
            default:
                $query = new CompatibleSearchQuery($search);
                break;
        } 
        Dispatcher::dispatchEvent('onSearchQuery', [$query, $search]);
        return $query;
    }

    public static function deleteURL(URL $url)
    {
        DB::query()
            ->delete('search_index')
            ->where('url = ?', [$url->fullPathString()])
            ->execute();
    }

    /**
     * Turns an input string into a plain text representation so it can be
     * searched usefully.
     *
     * @param string $body
     * @return string
     */
    protected static function cleanBody(string $body): string
    {
        // call dispatcher for initial work
        Dispatcher::dispatchEvent('onSearchIndexBody', [&$body]);
        // do initial stripping by adding whitespace where tags were
        $body = preg_replace('/\<base64.*?\>.+?\<\/base64\>/im', '', $body);
        $body = preg_replace('/\<.+?\>/m', ' ', $body);
        // fully and properly strip tags
        $body = strip_tags($body);
        // make lower case for sqlite
        if (DB::driver() == 'sqlite') $body = strtolower($body);
        // return
        return trim($body);
    }
}
