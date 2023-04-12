<?php

namespace DigraphCMS\UI\Pagination;

interface FilterToolInterface
{
    /**
     * Should return an array of strings, each being an order clause that will
     * be added to the query.
     *
     * @return array<int,string>
     */
    public function getOrderClauses(): array;

    /**
     * Should return an array of arrays. Each item should be an array containing:
     *  * first: The where clause to be added
     *  * second: The paramaters to be added, optionally
     *
     * @return array<int,array<int,mixed>>
     */
    public function getWhereClauses(): array;

    /**
     * Should return an array of arrays. Each item should be an array containing:
     *  * first: The column to be matched on
     *  * second: The query string to be wrapped and used as a LIKE query
     *
     * @return array<int,array<int,string>>
     */
    public function getLikeClauses(): array;

    /**
     * Should return an array of strings, which will be used as left joins on
     * the original query.
     *
     * @return array
     */
    public function getJoinClauses(): array;

    /**
     * Get the ID to use for this filter in the URL settings argument.
     *
     * @return string
     */
    public function getFilterID(): string;

    /**
     * Pass this filter the PaginatedSection it belongs to so it can get any 
     * data it needs from it.
     *
     * @param PaginatedSection $section
     * @return void
     */
    public function setSection(PaginatedSection $section);
}
