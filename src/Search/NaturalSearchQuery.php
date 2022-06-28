<?php

namespace DigraphCMS\Search;

class NaturalSearchQuery extends AbstractSearchQuery
{
    /**
     * Called when setSearch is run, should build the SQL necessary for running
     * the query.
     * 
     * Uses MySQL's built-in natural language search.
     *
     * @return void
     */
    protected function buildQuery()
    {
        $this->where('MATCH (body) AGAINST (? IN NATURAL LANGUAGE MODE)', [$this->search]);
    }
}
