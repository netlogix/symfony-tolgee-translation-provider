<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\InContext;

/**
 * Maps translation domains the way TolgeeProvider stores them: each domain is a Tolgee namespace of the same name.
 */
class DomainNamespaceKeyMapper implements KeyMapper
{
    /**
     * @var list<string>
     */
    private array $domains;

    private string $defaultDomain;

    /**
     * @param list<string> $domains
     */
    public function __construct(array $domains = ['messages'], string $defaultDomain = 'messages')
    {
        $this->domains = $domains;
        $this->defaultDomain = $defaultDomain;
    }

    public function map(string $id, ?string $domain): ?TolgeeKey
    {
        $domain = $domain ?? $this->defaultDomain;
        if (!in_array($domain, $this->domains, true)) {
            return null;
        }

        return new TolgeeKey($id, $domain);
    }
}
