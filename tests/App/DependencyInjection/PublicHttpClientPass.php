<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\App\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PublicHttpClientPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('http_client')->setPublic(true);
    }
}
