<?php

namespace App\Exception\Provider;

/**
 * Thrown when API request fails (network, timeout, HTTP errors)
 */
class ProviderFetchException extends ProviderException
{
    private ?string $apiUrl = null;
    private ?int $statusCode = null;

    public static function create(
        string $providerName,
        string $message,
        ?string $apiUrl = null,
        ?int $statusCode = null,
        ?\Throwable $previous = null
    ): self {
        $exception = new self($message, $providerName, 0, $previous);
        $exception->apiUrl = $apiUrl;
        $exception->statusCode = $statusCode;
        return $exception;
    }

    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'api_url' => $this->apiUrl,
            'status_code' => $this->statusCode,
        ]);
    }
}

