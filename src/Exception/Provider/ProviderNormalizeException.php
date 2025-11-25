<?php

namespace App\Exception\Provider;

/**
 * Thrown when data normalization/validation fails
 */
class ProviderNormalizeException extends ProviderException
{
    private ?string $contentId = null;
    private ?array $invalidData = null;

    public static function create(
        string $providerName,
        string $message,
        ?string $contentId = null,
        ?array $invalidData = null,
        ?\Throwable $previous = null
    ): self {
        $exception = new self($message, $providerName, 0, $previous);
        $exception->contentId = $contentId;
        $exception->invalidData = $invalidData ? self::limitArraySize($invalidData, 10) : null;
        return $exception;
    }

    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'content_id' => $this->contentId,
            'invalid_data' => $this->invalidData,
        ]);
    }

    /**
     * @param array $data
     * @param int $maxItems
     * @return array
     */
    private static function limitArraySize(array $data, int $maxItems = 10): array
    {
        if (count($data) <= $maxItems) {
            return $data;
        }

        $limited = array_slice($data, 0, $maxItems, true);
        $limited['_truncated'] = sprintf('... (%d more items)', count($data) - $maxItems);
        
        return $limited;
    }
}

