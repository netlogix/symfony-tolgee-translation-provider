<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Symfony;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Test\AbstractProviderFactoryTestCase;
use Symfony\Component\Translation\Test\IncompleteDsnTestTrait;
use Symfony\Component\Translation\Test\ProviderFactoryTestCase;

// symfony/translation 8.0 renamed `ProviderFactoryTestCase` to
// `AbstractProviderFactoryTestCase`, dropped its getClient()/getLogger()/
// getDefaultLocale()/getLoader() convenience helpers and $loader property,
// and moved the incomplete-DSN test into a separate trait that must now be
// composed in explicitly. This shim keeps a single test class working across
// symfony/translation ^5.4 - ^8.0.
if (class_exists(ProviderFactoryTestCase::class)) {
    abstract class ProviderFactoryTestCaseCompat extends ProviderFactoryTestCase
    {
    }
} else {
    abstract class ProviderFactoryTestCaseCompat extends AbstractProviderFactoryTestCase
    {
        use IncompleteDsnTestTrait;

        protected LoaderInterface|MockObject $loader;

        protected function getLoader(): LoaderInterface
        {
            return $this->loader ??= $this->createMock(LoaderInterface::class);
        }
    }
}
