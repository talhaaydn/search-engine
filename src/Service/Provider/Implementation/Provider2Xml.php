<?php

namespace App\Service\Provider\Implementation;

use App\DTO\ContentDTO;
use App\Enum\ContentType;
use App\Service\Provider\Base\BaseProvider;
use App\Service\Utility\DurationParser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Provider2Xml extends BaseProvider
{
    private const API_URL = 'https://raw.githubusercontent.com/WEG-Technology/mock/refs/heads/main/v2/provider2';

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
        $xml = simplexml_load_string($raw);
        $json = json_encode($xml);
        $decoded = json_decode($json, true);
        
        return $decoded['items']['item'] ?? [];
    }

    /**
     * @param array $raw
     * @return ContentDTO[]
     */
    public function normalize(array $raw): array
    {
        $dtos = [];
        
        // XML'den gelen veri tek bir item olabilir veya array olabilir
        if (isset($raw['id'])) {
            $raw = [$raw];
        }
        
        foreach ($raw as $item) {
            $dto = new ContentDTO();
            $dto->provider = $this->getName();
            $dto->contentId = $item['id'] ?? '';
            $dto->title = $item['headline'] ?? '';
            
            $typeString = $item['type'] ?? throw new \InvalidArgumentException(
                sprintf('Content type is required for content ID: %s', $item['id'] ?? 'unknown')
            );
            
            $dto->type = ContentType::tryFrom($typeString) ?? throw new \InvalidArgumentException(
                sprintf(
                    'Invalid content type "%s" for content ID: %s. Allowed types: %s',
                    $typeString,
                    $item['id'] ?? 'unknown',
                    implode(', ', array_map(fn($case) => $case->value, ContentType::cases()))
                )
            );
            
            $stats = $item['stats'] ?? [];
            $dto->views = (int)($stats['views'] ?? 0);
            $dto->likes = (int)($stats['likes'] ?? 0);
            $dto->reactions = (int)($stats['reactions'] ?? 0);
            $dto->comments = (int)($stats['comments'] ?? 0);
            
            $duration = $stats['duration'] ?? '';
            $dto->duration = $this->durationParser->parseDuration($duration);
            
            $readingTime = $stats['reading_time'] ?? '';
            $dto->readingTime = $this->durationParser->parseDuration($readingTime);
            
            $dto->publishedAt = new \DateTime($item['publication_date'] ?? 'now');
            
            $categories = $item['categories']['category'] ?? [];
            if (is_string($categories)) {
                $dto->tags = [$categories];
            } else {
                $dto->tags = $categories;
            }
            
            $dtos[] = $dto;
        }
        
        return $dtos;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'provider_2_xml';
    }
}

