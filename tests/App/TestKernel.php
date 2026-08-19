<?php

declare(strict_types=1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\App;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestKernel extends \Symfony\Component\HttpKernel\Kernel
{

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Netlogix\SymfonyTolgeeTranslationProvider\TolgeeTranslationProviderBundle(),
        ];
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new \Netlogix\SymfonyTolgeeTranslationProvider\Test\App\DependencyInjection\PublicHttpClientPass()
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'secret' => 'SOME_SECRET',
                'test' => true,
                "enabled_locales" => ["en", "de"],
                "translator" => [
                    "enabled" => true,
                    "default_path" => '%kernel.project_dir%/translations',
                    "providers" => [
                        "tolgee" => [
                            "dsn" => '%env(TOLGEE_DSN)%'
                        ]
                    ]
                ]
            ]);
        });
    }
}
