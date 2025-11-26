<?php

namespace App\Service\Provider\Manager;

use App\DTO\ContentDTO;
use App\Exception\Provider\ProviderException;
use App\Exception\Provider\RateLimitExceededException;
use App\Service\Provider\Interface\ProviderInterface;
use Psr\Log\LoggerInterface;

class ProviderManager
{
    /**
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return ContentDTO[]
     */
    public function importAll(): array
    {
        $contents = [];

        foreach ($this->providers as $provider) {
            $dtos = $this->processProvider($provider);

            if ($dtos) {
                $contents = array_merge($contents, $dtos);
            }
        }
        
        return $contents;
    }

    /**
     * @param ProviderInterface $provider
     * @return ContentDTO[]|null
     */
    private function processProvider(ProviderInterface $provider): ?array
    {        
        $this->logger->info('Provider starting', [
            'provider' => $provider->getName(),
        ]);

        try {
            $raw = $provider->fetch();
            $dtos = $provider->normalize($raw);

            $this->logger->info('Provider completed successfully', [
                'provider' => $provider->getName(),
                'content_count' => count($dtos)
            ]);

            return $dtos;

        } catch (RateLimitExceededException $e) {
            $this->logRateLimitExceededException($provider->getName(), $e);

        } catch (ProviderException $e) {
            $this->logProviderException($provider->getName(), $e);

        } catch (\Throwable $e) {
            $this->logUnexpectedException($provider->getName(), $e);
        }

        return null;
    }

    /**
     * @param string $providerName
     * @param ProviderException $e
     */
    private function logProviderException(string $providerName, ProviderException $e): void
    {
        $this->logger->error('Provider error', array_merge(
            $e->getContext(),
            [
                'provider' => $providerName,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]
        ));
    }

    /**
     * @param string $providerName
     * @param \Throwable $e
     */
    private function logUnexpectedException(string $providerName, \Throwable $e): void
    {
        $this->logger->critical('Unexpected provider error', [
            'provider' => $providerName,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * @param string $providerName
     * @param RateLimitExceededException $e
     */
    private function logRateLimitExceededException(string $providerName, RateLimitExceededException $e): void
    {
        $this->logger->warning('Provider rate limit exceeded', [
            'provider' => $providerName,
            'retry_after' => $e->getContext()['retry_after'] ?? 'unknown',
        ]);
    }
}

