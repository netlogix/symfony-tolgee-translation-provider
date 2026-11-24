<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\InContext;

interface InContextState
{
    public function isActive(): bool;
}
