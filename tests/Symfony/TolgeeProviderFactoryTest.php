<?php

declare(strict_types=1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Symfony;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Netlogix\SymfonyTolgeeTranslationProvider\TolgeeProviderFactory as ProviderFactory;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Provider\Dsn;
use Symfony\Component\Translation\Provider\ProviderFactoryInterface;
use Symfony\Component\Translation\Test\ProviderFactoryTestCase;

class TolgeeProviderFactoryTest extends ProviderFactoryTestCase
{
    public static function supportsProvider(): iterable
    {
        yield "http" => [true, 'tolgee://1:API_KEY@tolgee.dev'];
        yield "https" => [true, 'tolgees://2:API_KEY@tolgee.dev:8080'];
        yield "wrong shema" => [false, 'somethingElse://1:API_KEY@app.tolgee.io'];
    }

    public static function unsupportedSchemeProvider(): iterable
    {
        yield "wrong shema" => ['somethingElse://1:API_KEY@app.tolgee.io'];
    }

    public static function createProvider(): iterable
    {
        yield 'http' => [
            'tolgee://app.tolgee.io',
            'tolgee://2:API_KEY@app.tolgee.io'
        ];
        yield 'https' => [
            'tolgee://tolgee.dev:8080',
            'tolgees://2:API_KEY@tolgee.dev:8080'
        ];
    }

    public static function incompleteDsnProvider(): iterable
    {
        yield "mising password and user - http" => [
            'tolgee://default',
            'Invalid "tolgee://default" provider DSN: User is not set.'

        ];
        yield "mising password and user - https" => [
            'tolgees://default',
            'Invalid "tolgees://default" provider DSN: User is not set.'
        ];
        yield "wrong filter" => [
            'tolgee://1:API_KEY@tolgee.dev/foo'
        ];
    }

    public function testBaseUri()
    {
        $zip = new \ZipArchive();
        $tmpZip = tempnam(sys_get_temp_dir(), 'test_export_') . ".zip";
        $zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('messages/en.json', json_encode(['foo' => 'bar']));
        $zip->close();
        $zipContent = file_get_contents($tmpZip);
        unlink($tmpZip);

        $response = new MockResponse($zipContent);
        $httpClient = new MockHttpClient([$response]);
        $loader = $this->getLoader();
        $factory = new ProviderFactory($httpClient, $this->getLogger(),  $this->getDefaultLocale(), $loader);
        $provider = $factory->create(new Dsn('tolgees://2:API_KEY@tolgee.dev:8080'));

        // Make a real HTTP request.
        $provider->read(['messages'], ['en']);

        $this->assertStringContainsString('export', $response->getRequestUrl());
        $this->assertStringContainsString('filterNamespace=messages', $response->getRequestUrl());
        $this->assertStringContainsString('languages=en', $response->getRequestUrl());
        $this->assertStringContainsString('format=JSON', $response->getRequestUrl());
        $this->assertStringContainsString('zip=1', $response->getRequestUrl());
    }

    public function createFactory(): ProviderFactoryInterface
    {
        return new ProviderFactory($this->getClient(), $this->getLogger(), $this->getDefaultLocale(), $this->getLoader());
    }

    protected function getLoader(): ArrayLoader
    {
        return $this->loader ?? $this->loader = new ArrayLoader();
    }
}
