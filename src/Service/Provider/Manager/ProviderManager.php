<?php

namespace App\Service\Provider\Manager;

use App\DTO\ContentDTO;
use App\Service\Provider\Interface\ProviderInterface;

class ProviderManager
{
    /**
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers
    ) {
    }

    /**
     * @return ContentDTO[]
     */
    public function importAll(): array
    {
        $contents = [];

        foreach ($this->providers as $provider) {
            $raw = $provider->fetch();
            $dtos = $provider->normalize($raw);
            
            $contents = array_merge($contents, $dtos);
        }

        return $contents;
    }
}

