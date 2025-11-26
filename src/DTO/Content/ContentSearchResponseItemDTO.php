<?php

namespace App\DTO\Content;

use App\Enum\ContentType;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ContentSearchResponseItemDTO',
    description: 'Content search response item'
)]
class ContentSearchResponseItemDTO
{
    #[OA\Property(property: 'id', type: 'integer', description: 'Content ID')]
    private int $id;

    #[OA\Property(property: 'title', type: 'string', description: 'Content title')]
    private string $title;

    #[OA\Property(property: 'contentType', type: 'string', enum: ['video', 'article'], description: 'Content type')]
    private ContentType $contentType;

    #[OA\Property(property: 'score', type: 'number', format: 'float', description: 'Content score')]
    private float $score;

    #[OA\Property(property: 'createdAt', type: 'string', format: 'date-time', description: 'Creation date')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        int $id,
        string $title,
        ContentType $contentType,
        float $score,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->contentType = $contentType;
        $this->score = $score;
        $this->createdAt = $createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContentType(): ContentType
    {
        return $this->contentType;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'contentType' => $this->contentType->value,
            'score' => $this->score,
            'createdAt' => $this->createdAt->format('c'),
        ];
    }
}

