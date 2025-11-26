<?php

namespace App\DTO\Pagination;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginationMetaDTO',
    description: 'Pagination metadata information'
)]
class PaginationMetaDTO
{
    #[OA\Property(property: 'total', type: 'integer', description: 'Total number of items', example: 150)]
    private int $total;

    #[OA\Property(property: 'page', type: 'integer', description: 'Current page number', example: 1)]
    private int $page;

    #[OA\Property(property: 'limit', type: 'integer', description: 'Number of items per page', example: 20)]
    private int $limit;

    #[OA\Property(property: 'totalPages', type: 'integer', description: 'Total number of pages', example: 8)]
    private int $totalPages;

    #[OA\Property(property: 'hasNextPage', type: 'boolean', description: 'Whether there is a next page', example: true)]
    private bool $hasNextPage;

    #[OA\Property(property: 'hasPreviousPage', type: 'boolean', description: 'Whether there is a previous page', example: false)]
    private bool $hasPreviousPage;

    public function __construct(int $total, int $page, int $limit)
    {
        $this->total = $total;
        $this->page = $page;
        $this->limit = $limit;
        $this->totalPages = (int) ceil($total / $limit);
        $this->hasNextPage = $page < $this->totalPages;
        $this->hasPreviousPage = $page > 1;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function hasNextPage(): bool
    {
        return $this->hasNextPage;
    }

    public function hasPreviousPage(): bool
    {
        return $this->hasPreviousPage;
    }
}

