<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\InContext;

final class TolgeeKey
{
    private string $key;

    private ?string $namespace;

    public function __construct(string $key, ?string $namespace = null)
    {
        $this->key = $key;
        $this->namespace = $namespace;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }
}
