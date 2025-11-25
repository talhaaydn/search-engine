<?php

namespace App\Service\Provider\Implementation;

use App\DTO\ContentDTO;
use App\Enum\ContentType;
use App\Exception\Provider\ProviderNormalizeException;
use App\Exception\Provider\ProviderParseException;
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
     * @throws ProviderParseException
     */
    protected function parse(string $raw): array
    {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        
        if (!isset($decoded['contents']) || !is_array($decoded['contents'])) {
            throw ProviderParseException::create(
                providerName: $this->getName(),
                message: 'Invalid response structure: "contents" field missing or not an array',
                rawData: $raw
            );
        }
        
        return $decoded['contents'];
    }

    /**
     * @param array $raw
     * @return ContentDTO[]
     * @throws ProviderNormalizeException
     */
    public function normalize(array $raw): array
    {
        $dtos = [];
        $errors = [];
        
        foreach ($raw as $index => $item) {
            try {
                $dtos[] = $this->normalizeItem($item);
            } catch (\Throwable $e) {
                $errors[] = [
                    'index' => $index,
                    'content_id' => $item['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        if (empty($dtos) && !empty($raw)) {
            throw ProviderNormalizeException::create(
                providerName: $this->getName(),
                message: sprintf('Failed to normalize all %d items', count($raw)),
                invalidData: ['errors' => $errors]
            );
        }
        
        return $dtos;
    }

    /**
     * @param array $item
     * @return ContentDTO
     * @throws ProviderNormalizeException
     */
    private function normalizeItem(array $item): ContentDTO
    {        
        $contentId = $item['id'] ?? null;

        try {
            $dto = new ContentDTO();
            $dto->provider = $this->getName();
            
            if (empty($contentId)) {
                throw new \InvalidArgumentException('Content ID is required');
            }

            $dto->contentId = (string)$contentId;
            $dto->title = $item['title'] ?? '';
            
            $typeString = $item['type'] ?? null;
            if (empty($typeString)) {
                throw new \InvalidArgumentException('Content type is required');
            }
            
            $dto->type = ContentType::tryFrom($typeString);
            if ($dto->type === null) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid content type "%s". Allowed types: %s',
                        $typeString,
                        implode(', ', array_map(fn($case) => $case->value, ContentType::cases()))
                    )
                );
            }
            
            $metrics = $item['metrics'] ?? [];
            $dto->views = (int)($metrics['views'] ?? 0);
            $dto->likes = (int)($metrics['likes'] ?? 0);
            $dto->reactions = 0;
            $dto->comments = 0;
            
            $duration = $metrics['duration'] ?? '';
            $dto->duration = $this->durationParser->parseDuration($duration);
            $dto->readingTime = $dto->duration;
            
            $publishedAt = $item['published_at'] ?? null;
            if (empty($publishedAt)) {
                throw new \InvalidArgumentException('Published at is required');
            }
            $dto->publishedAt = new \DateTime($publishedAt);
            
            $dto->tags = $item['tags'] ?? [];
            
            return $dto;
            
        } catch (\Throwable $e) {
            throw ProviderNormalizeException::create(
                providerName: $this->getName(),
                message: sprintf('Failed to normalize item: %s', $e->getMessage()),
                contentId: $contentId,
                invalidData: $item,
                previous: $e
            );
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'provider_1_json';
    }
}

