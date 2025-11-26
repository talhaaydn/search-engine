<?php

namespace App\Exception\Provider;

class RateLimitExceededException extends ProviderException
{
    public static function create(
        string $providerName,
        ?string $retryAfter = null,
        ?\Throwable $previous = null
    ): self {
        $message = sprintf(
            'Rate limit exceeded for provider "%s".',
            $providerName
        );

        $exception = new self($message, $providerName, 0, $previous);
        
        if ($retryAfter !== null) {
            $exception->context['retry_after'] = $retryAfter;
        }

        return $exception;
    }
}

