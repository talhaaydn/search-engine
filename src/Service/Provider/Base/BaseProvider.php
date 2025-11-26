<?php

namespace App\Service\Provider\Base;

use App\Exception\Provider\ProviderFetchException;
use App\Exception\Provider\ProviderParseException;
use App\Exception\Provider\RateLimitExceededException;
use App\Service\Provider\Interface\ProviderInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class BaseProvider implements ProviderInterface
{
    private ?RateLimiterFactory $rateLimiterFactory = null;
    
    public function setRateLimiter(RateLimiterFactory $rateLimiterFactory): void
    {
        $this->rateLimiterFactory = $rateLimiterFactory;
    }

    /**
     * @return array
     * @throws ProviderFetchException
     * @throws ProviderParseException
     * @throws RateLimitExceededException
     */
    public function fetch(): array
    {
        $this->checkRateLimit();
        
        $raw = $this->fetchWithErrorHandling();
        return $this->parseWithErrorHandling($raw);
    }

    /**
     * @throws RateLimitExceededException
     */
    private function checkRateLimit(): void
    {
        if ($this->rateLimiterFactory === null) {
            return;
        }

        $limiter = $this->rateLimiterFactory->create($this->getName());
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = null;
            $retryAfterDate = $limit->getRetryAfter();
            if ($retryAfterDate !== null) {
                $retryAfter = $retryAfterDate->format('Y-m-d H:i:s');
            }
            
            throw RateLimitExceededException::create(
                providerName: $this->getName(),
                retryAfter: $retryAfter
            );
        }
    }

    /**
     * @return string
     * @throws ProviderFetchException
     */
    private function fetchWithErrorHandling(): string
    {
        try {
            return $this->request();
        } catch (\Throwable $e) {
            throw $this->wrapFetchException($e);
        }
    }

    /**
     * @param string $raw
     * @return array
     * @throws ProviderParseException
     */
    private function parseWithErrorHandling(string $raw): array
    {
        try {
            return $this->parse($raw);
        } catch (\Throwable $e) {
            throw $this->wrapParseException($e, $raw);
        }
    }

    /**
     * @param \Throwable $e
     * @return ProviderFetchException
     */
    private function wrapFetchException(\Throwable $e): ProviderFetchException
    {
        if ($e instanceof TransportExceptionInterface) {
            return ProviderFetchException::create(
                providerName: $this->getName(),
                message: sprintf('Network error: %s', $e->getMessage()),
                previous: $e
            );
        }

        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getResponse()->getStatusCode();
            return ProviderFetchException::create(
                providerName: $this->getName(),
                message: sprintf('HTTP error %d: %s', $statusCode, $e->getMessage()),
                statusCode: $statusCode,
                previous: $e
            );
        }

        return ProviderFetchException::create(
            providerName: $this->getName(),
            message: sprintf('Request failed: %s', $e->getMessage()),
            previous: $e
        );
    }

    /**
     * @param \Throwable $e
     * @param string $raw
     * @return ProviderParseException
     */
    private function wrapParseException(\Throwable $e, string $raw): ProviderParseException
    {
        $message = $e instanceof \JsonException 
            ? sprintf('JSON parse error: %s', $e->getMessage())
            : sprintf('Parse error: %s', $e->getMessage());

        return ProviderParseException::create(
            providerName: $this->getName(),
            message: $message,
            rawData: $raw,
            previous: $e
        );
    }

    /**
     * @return string
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
     */
    abstract protected function request(): string;

    /**
     * @param string $raw
     * @return array
     * @throws \JsonException
     */
    abstract protected function parse(string $raw): array;
}

