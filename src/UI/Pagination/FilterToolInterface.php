<?php

namespace DigraphCMS\UI\Pagination;

interface FilterToolInterface
{
    public function getOrderClauses(): array;
    public function getWhereClauses(): array;
    public function getFilterID(): string;
    public function setPaginator(PaginatedSection $paginator);
}
