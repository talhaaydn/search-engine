<?php

namespace App\DTO\Elasticsearch;

use App\Enum\ContentType;

readonly class ContentDocumentDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public ContentType $contentType,
        public float $score,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'contentType' => $this->contentType->value,
            'score' => $this->score,
            'createdAt' => $this->createdAt->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            contentType: ContentType::from($data['contentType']),
            score: $data['score'],
            createdAt: new \DateTimeImmutable($data['createdAt']),
        );
    }

    public static function fromEntity(\App\Entity\Content $content): self
    {
        return new self(
            id: $content->getId(),
            title: $content->getTitle(),
            contentType: $content->getContentType(),
            score: $content->getScore(),
            createdAt: $content->getCreatedAt(),
        );
    }
}

