<?php

namespace DigraphCMS\Search;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\DB\DB;
use DigraphCMS\URL\URL;

abstract class AbstractSearchQuery extends AbstractMappedSelect
{
    protected $search;

    /**
     * Called when setSearch is run, should build the SQL necessary for running
     * the query.
     *
     * @return void
     */
    abstract protected function buildQuery();
    
    public function __construct(string $search)
    {
        parent::__construct(DB::query()->from('search_index'));
        $this->setSearch($search);
    }

    protected function setSearch(string $search)
    {
        $this->search = strtolower($search);
        $this->where(null);
        $this->order(null);
        $this->buildQuery();
    }

    /**
     * @param array $row
     * @return SearchResult
     */
    protected function doRowToObject(array $row)
    {
        return new SearchResult(
            $row['title'],
            new URL($row['url']),
            $row['body'],
            $this->search
        );
    }
}
