<?php

namespace DigraphCMS\Search;

use DigraphCMS\DB\DB;

class BooleanSearchQuery extends AbstractSearchQuery
{
    /**
     * Called when setSearch is run, should build the SQL necessary for running
     * the query.
     * 
     * Uses MySQL's built-in boolean search.
     *
     * @return void
     */
    protected function buildQuery()
    {
        $this->where('MATCH (body) AGAINST (' . DB::pdo()->quote($this->search) . ' IN BOOLEAN MODE)');
    }
}
