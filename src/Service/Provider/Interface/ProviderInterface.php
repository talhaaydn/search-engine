<?php

namespace App\Service\Provider\Interface;

use App\DTO\ContentDTO;

interface ProviderInterface
{
    /**
     * @return array
     */
    public function fetch(): array;

    /**
     * @param array $raw
     * @return ContentDTO[]
     */
    public function normalize(array $raw): array;

    /**
     * @return string
     */
    public function getName(): string;
}

