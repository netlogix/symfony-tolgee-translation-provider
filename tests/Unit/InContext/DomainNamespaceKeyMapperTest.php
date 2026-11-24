<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Unit\InContext;

use Netlogix\SymfonyTolgeeTranslationProvider\InContext\DomainNamespaceKeyMapper;
use Netlogix\SymfonyTolgeeTranslationProvider\InContext\TolgeeKey;
use PHPUnit\Framework\TestCase;

class DomainNamespaceKeyMapperTest extends TestCase
{
    public function testMapsTheDomainToTheNamespaceOfTheSameName(): void
    {
        self::assertEquals(
            new TolgeeKey('checkout.title', 'messages'),
            (new DomainNamespaceKeyMapper())->map('checkout.title', 'messages')
        );
    }

    public function testUsesTheDefaultDomainWithoutDomain(): void
    {
        self::assertEquals(
            new TolgeeKey('checkout.title', 'messages'),
            (new DomainNamespaceKeyMapper())->map('checkout.title', null)
        );
    }

    public function testSkipsDomainsThatAreNotConfigured(): void
    {
        self::assertNull((new DomainNamespaceKeyMapper())->map('This value is not valid.', 'validators'));
    }

    public function testMapsAdditionallyConfiguredDomains(): void
    {
        self::assertEquals(
            new TolgeeKey('This value is not valid.', 'validators'),
            (new DomainNamespaceKeyMapper(['messages', 'validators']))->map('This value is not valid.', 'validators')
        );
    }
}
