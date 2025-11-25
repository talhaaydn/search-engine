<?php

namespace App\Exception\Provider;

/**
 * Base exception for all provider-related errors
 */
abstract class ProviderException extends \Exception
{
    protected string $providerName;

    public function __construct(
        string $message = "",
        string $providerName = 'unknown',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->providerName = $providerName;
        parent::__construct($message, $code, $previous);
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }
    
    public function getContext(): array
    {
        return [
            'provider' => $this->providerName,
            'code' => $this->getCode(),
        ];
    }
}

