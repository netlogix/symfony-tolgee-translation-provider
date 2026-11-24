<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\InContext;

interface KeyMapper
{
    /**
     * Returns null for translations that are not managed in Tolgee and must stay unmarked.
     */
    public function map(string $id, ?string $domain): ?TolgeeKey;
}
