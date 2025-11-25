<?php

namespace App\Exception\Provider;

/**
 * Thrown when data parsing fails (invalid JSON, XML, etc.)
 */
class ProviderParseException extends ProviderException
{
    private ?string $rawDataSample = null;

    public static function create(
        string $providerName,
        string $message,
        ?string $rawData = null,
        ?\Throwable $previous = null
    ): self {
        $exception = new self($message, $providerName, 0, $previous);
        $exception->rawDataSample = $rawData ? substr($rawData, 0, 200) : null;
        return $exception;
    }

    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'raw_data_sample' => $this->rawDataSample,
        ]);
    }
}

