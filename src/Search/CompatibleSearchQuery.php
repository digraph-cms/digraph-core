<?php

namespace DigraphCMS\Search;

use DigraphCMS\DB\DB;

class CompatibleSearchQuery extends AbstractSearchQuery
{
    /**
     * Called when setSearch is run, should build the SQL necessary for running
     * the query.
     * 
     * this search method is the fallback, for when full text indexes are not 
     * available. It's not very advanced (or really advanced at all), and it's 
     * very very slow, but it works on SQLite.
     *
     * @return void
     */
    protected function buildQuery()
    {
        if (DB::driver() != 'sqlite') throw new \Exception("Only SQLite is supported by CompatibleSearchQuery");
        $search = preg_replace('/[\%_]/', '', $this->search);
        $words = array_filter(
            explode(
                ' ',
                $search
            ),
            function ($word) {
                return strlen($word) > 2;
            }
        );
        $order = [];
        foreach ($words as $word) {
            $this->where('instr(body,?)', [$word]);
            $order[] = 'instr(body,?)';
        }
        // basic ordering is how close matches are to top
        if ($order) $this->order(implode('+', $order) . ' asc', $words);
        // ordering tie breaker is id, so newer things go higher
        $this->order('id desc');
    }
}
