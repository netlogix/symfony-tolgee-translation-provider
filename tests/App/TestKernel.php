<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\App;

use Netlogix\SymfonyTolgeeTranslationProvider\Test\App\DependencyInjection\PublicHttpClientPass;
use Netlogix\SymfonyTolgeeTranslationProvider\TolgeeTranslationProviderBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TolgeeTranslationProviderBundle()
        ];
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PublicHttpClientPass());
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'SOME_SECRET',
                'test' => true,
                'enabled_locales' => ['en', 'de'],
                'translator' => [
                    'enabled' => true,
                    'default_path' => '%kernel.project_dir%/translations',
                    'providers' => [
                        'tolgee' => [
                            'dsn' => '%env(TOLGEE_DSN)%'
                        ]
                    ]
                ]
            ]);
        });
    }
}
