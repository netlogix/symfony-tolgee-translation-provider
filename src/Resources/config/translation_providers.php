<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Netlogix\SymfonyTolgeeTranslationProvider\TolgeeProviderFactory;
use Symfony\Component\Translation\Loader\ArrayLoader;

// @codeCoverageIgnoreStart
return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services
        ->set('translation.loader.array', ArrayLoader::class)
        ->tag('translation.loader', ['alias' => 'array']);

    $services
        ->set('translation.provider_factory.tolgee', TolgeeProviderFactory::class)
        ->autowire(true)
        ->args([
            '$client' => service('http_client'),
            '$logger' => service('logger'),
            '$defaultLocale' => param('kernel.default_locale'),
            '$loader' => service('translation.loader.array'),
        ])
        ->tag('translation.provider_factory');
};
// @codeCoverageIgnoreEnd