<?php

namespace App\Service\Provider\Base;

use App\Service\Provider\Interface\ProviderInterface;

abstract class BaseProvider implements ProviderInterface
{
    /**
     * @return array
     */
    public function fetch(): array
    {
        $raw = $this->request();
        return $this->parse($raw);
    }

    /**
     * @return string
     */
    abstract protected function request(): string;

    /**
     * @param string $raw
     * @return array
     */
    abstract protected function parse(string $raw): array;
}

