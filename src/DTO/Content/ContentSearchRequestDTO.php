<?php

namespace App\DTO\Content;

use App\Enum\ContentType;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'ContentSearchRequestDTO',
    description: 'Content search parameters'
)]
class ContentSearchRequestDTO
{
    public function __construct(
        #[OA\Property(
            property: 'keyword',
            description: 'Search keyword',
            type: 'string',
            minLength: 2,
            maxLength: 255,
            nullable: true
        )]
        #[Assert\Length(min: 2, max: 255)]
        private readonly ?string $keyword = null,

        #[OA\Property(
            property: 'contentType',
            description: 'Content type filter',
            type: 'string',
            enum: ['video', 'article'],
            nullable: true
        )]
        private readonly ?ContentType $contentType = null,

        #[OA\Property(
            property: 'sortByScore',
            description: 'Sort order by score field',
            type: 'string',
            enum: ['asc', 'desc'],
            nullable: true
        )]
        #[Assert\Choice(choices: ['asc', 'desc'])]
        private readonly ?string $sortByScore = null,

        #[OA\Property(
            property: 'page',
            description: 'Page number',
            type: 'integer',
            minimum: 1,
            default: 1
        )]
        #[Assert\Positive]
        #[Assert\Range(min: 1)]
        private readonly int $page = 1,

        #[OA\Property(
            property: 'limit',
            description: 'Number of results per page',
            type: 'integer',
            minimum: 1,
            maximum: 100,
            default: 20
        )]
        #[Assert\Positive]
        #[Assert\Range(min: 1, max: 100)]
        private readonly int $limit = 20
    ) {
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function getContentType(): ?ContentType
    {
        return $this->contentType;
    }

    public function getSortByScore(): ?string
    {
        return $this->sortByScore;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    #[Ignore]
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}

