<?php

namespace DigraphCMS\UI\Pagination;

interface FilterToolInterface
{
    /**
     * Should return an array of strings, each being an order clause that will be added to the query
     *
     * @return array
     */
    public function getOrderClauses(): array;
    /**
     * Should return an array of arrays. Each item should be an array containing:
     *  * first: The where clause to be added
     *  * second: The paramaters to be added, optionally
     *
     * @return array
     */
    public function getWhereClauses(): array;
    public function getFilterID(): string;
    public function setPaginator(PaginatedSection $paginator);
}
