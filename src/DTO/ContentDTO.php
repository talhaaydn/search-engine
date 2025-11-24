<?php

namespace App\DTO;

use App\Enum\ContentType;

class ContentDTO
{
    public string $provider;
    public string $contentId;
    public string $title;
    public ContentType $type;

    public int $views = 0;
    public int $likes = 0;
    public int $duration = 0;

    public int $readingTime = 0;
    public int $reactions = 0;
    public int $comments = 0;

    public \DateTime $publishedAt;
    public array $tags = [];
}

