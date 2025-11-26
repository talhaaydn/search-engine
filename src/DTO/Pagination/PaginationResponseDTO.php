<?php

namespace App\DTO\Pagination;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginationResponseDTO',
    description: 'Generic paginated response with data and metadata'
)]
class PaginationResponseDTO
{
    #[OA\Property(
        property: 'data',
        type: 'array',
        description: 'Array of items'
    )]
    private array $data;

    #[OA\Property(
        property: 'meta',
        ref: new Model(type: PaginationMetaDTO::class),
        description: 'Pagination metadata'
    )]
    private PaginationMetaDTO $meta;

    /**
     * @param array<int, mixed> $data
     */
    public function __construct(array $data, PaginationMetaDTO $meta)
    {
        $this->data = $data;
        $this->meta = $meta;
    }

    /**
     * @return array<int, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getMeta(): PaginationMetaDTO
    {
        return $this->meta;
    }
}

