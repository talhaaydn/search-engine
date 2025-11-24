<?php

namespace App\Service\Provider\Implementation;

use App\DTO\ContentDTO;
use App\Enum\ContentType;
use App\Service\Provider\Base\BaseProvider;
use App\Service\Utility\DurationParser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Provider1Json extends BaseProvider
{
    private const API_URL = 'https://raw.githubusercontent.com/WEG-Technology/mock/refs/heads/main/v2/provider1';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly DurationParser $durationParser
    ) {
    }

    /**
     * @return string
     */
    protected function request(): string
    {
        $response = $this->httpClient->request('GET', self::API_URL);
        return $response->getContent();
    }

    /**
     * @param string $raw
     * @return array
     */
    protected function parse(string $raw): array
    {
        $decoded = json_decode($raw, true);
        
        return $decoded['contents'] ?? [];
    }

    /**
     * @param array $raw
     * @return ContentDTO[]
     */
    public function normalize(array $raw): array
    {
        $dtos = [];
        
        foreach ($raw as $item) {
            $dto = new ContentDTO();
            $dto->provider = $this->getName();
            $dto->contentId = $item['id'] ?? '';
            $dto->title = $item['title'] ?? '';
            
            $typeString = $item['type'] ?? 'article';
            $dto->type = $typeString === 'video' ? ContentType::VIDEO : ContentType::ARTICLE;
            
            $metrics = $item['metrics'] ?? [];
            $dto->views = (int)($metrics['views'] ?? 0);
            $dto->likes = (int)($metrics['likes'] ?? 0);
            $dto->reactions = 0;
            $dto->comments = 0;
            
            $duration = $metrics['duration'] ?? '';
            $dto->duration = $this->durationParser->parseDuration($duration);
            $dto->readingTime = $dto->duration;
            
            $dto->publishedAt = new \DateTime($item['published_at'] ?? 'now');
            $dto->tags = $item['tags'] ?? [];
            
            $dtos[] = $dto;
        }
        
        return $dtos;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'provider_1_json';
    }
}

