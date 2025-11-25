<?php

namespace App\Service\Provider\Implementation;

use App\DTO\ContentDTO;
use App\Enum\ContentType;
use App\Exception\Provider\ProviderNormalizeException;
use App\Exception\Provider\ProviderParseException;
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
     * @throws ProviderParseException
     */
    protected function parse(string $raw): array
    {
        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_string($raw);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            $errorMessages = array_map(
                fn($error) => sprintf('Line %d: %s', $error->line, trim($error->message)),
                $errors
            );
            
            throw ProviderParseException::create(
                providerName: $this->getName(),
                message: sprintf('XML parse error: %s', implode('; ', $errorMessages)),
                rawData: $raw
            );
        }
        
        $json = json_encode($xml);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        
        if (!isset($decoded['items']['item']) || !is_array($decoded['items']['item'])) {
            throw ProviderParseException::create(
                providerName: $this->getName(),
                message: 'Invalid XML structure: "items/item" field missing or not an array',
                rawData: $raw
            );
        }
        
        return $decoded['items']['item'];
    }

    /**
     * @param array $raw
     * @return ContentDTO[]
     * @throws ProviderNormalizeException
     */
    public function normalize(array $raw): array
    {
        if (isset($raw['id'])) {
            $raw = [$raw];
        }
        
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
            
            $dto->title = $item['headline'] ?? '';
            
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
            
            $stats = $item['stats'] ?? [];
            $dto->views = (int)($stats['views'] ?? 0);
            $dto->likes = (int)($stats['likes'] ?? 0);
            $dto->reactions = (int)($stats['reactions'] ?? 0);
            $dto->comments = (int)($stats['comments'] ?? 0);
            
            $duration = $stats['duration'] ?? '';
            $dto->duration = $this->durationParser->parseDuration($duration);
            
            $readingTime = $stats['reading_time'] ?? '';
            $dto->readingTime = $this->durationParser->parseDuration($readingTime);
            
            $publicationDate = $item['publication_date'] ?? null;
            if (empty($publicationDate)) {
                throw new \InvalidArgumentException('Publication date is required');
            }
            $dto->publishedAt = new \DateTime($publicationDate);
            
            $categories = $item['categories']['category'] ?? [];
            if (is_string($categories)) {
                $dto->tags = [$categories];
            } else {
                $dto->tags = is_array($categories) ? $categories : [];
            }
            
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
        return 'provider_2_xml';
    }
}
