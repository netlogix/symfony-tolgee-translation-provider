<?php

declare(strict_types=1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Netlogix\SymfonyTolgeeTranslationProvider\Exception\TolgeeException;
use Netlogix\SymfonyTolgeeTranslationProvider\Test\Fixtures\HttpClientFixture;
use Netlogix\SymfonyTolgeeTranslationProvider\TolgeeProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TolgeeProviderPrivateTest extends TestCase
{
    const BASE_URI = 'https://tolgee.dev/v2/projects/1/';

    /**
     * @var ReflectionClass
     */
    private $reflection;

    public function setUp(): void
    {
        $this->reflection = new \ReflectionClass(TolgeeProvider::class);
    }

    public function createProvider(?HttpClientInterface $client = null): ProviderInterface
    {
        return new TolgeeProvider(
            $client ?? new MockHttpClient(),
            $this->createMock(ArrayLoader::class),
            $this->createMock(LoggerInterface::class),
            'en',
            ''
        );
    }

    private function invokeTolgeeProviderMethod(TolgeeProvider $provider, string $method, ...$args)
    {
        $method = $this->reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invoke($provider, ...$args);
    }

    public function testImportArgumentException()
    {
        $provider = $this->createProvider();
        $this->expectException(\InvalidArgumentException::class);
        $this->invokeTolgeeProviderMethod($provider, 'import', ['foo']);
    }

    public function testImport()
    {
        $provider = $this->createProvider(new MockHttpClient(
            function ($method, $url, $options) {

                self::assertArrayHasKey('headers', $options);
                self::assertArrayHasKey('body', $options);

                $foundMultiPart = array_reduce($options['headers'], function ($carry, $item) use (&$foundMultiPart) {
                    return $carry || strpos($item, 'multipart/form-data; boundary=') !== false;
                }, false);
                self::assertTrue($foundMultiPart);

                $path = str_replace(self::BASE_URI, '', $url);
                $data = HttpClientFixture::getData('TolgeeApi/ImportTest', $path, $method);
                return new MockResponse($data);
            },
            self::BASE_URI
        ));

        $response = $this->invokeTolgeeProviderMethod($provider, 'import', [
            new DataPart('{"foo":"bar en"}', 'en.json', 'application/json'),
            new DataPart('{"foo":"bar de"}', 'de.json', 'application/json')
        ]);

        self::assertEquals([
            0 => 1000040001,
            1 => 1000040002
        ], $response);
    }

    public function testImportError()
    {
        $provider = $this->createProvider(new \Symfony\Component\HttpClient\MockHttpClient(
            function ($method, $url, $options) {

                self::assertArrayHasKey('headers', $options);
                self::assertArrayHasKey('body', $options);

                $foundMultiPart = array_reduce($options['headers'], function ($carry, $item) use (&$foundMultiPart) {
                    return $carry || strpos($item, 'multipart/form-data; boundary=') !== false;
                }, false);
                self::assertTrue($foundMultiPart);

                $path = str_replace(self::BASE_URI, '', $url);
                $data = HttpClientFixture::getData('TolgeeApi/ImportTestError', $path, $method);
                return new \Symfony\Component\HttpClient\Response\MockResponse($data);
            },
            self::BASE_URI
        ));

        $this->expectException(TolgeeException::class);

        $this->invokeTolgeeProviderMethod($provider,'import',[
            new DataPart('', 'de.json', 'application/json')
        ]);
    }

    public function testGetLanguages()
    {
        $provider = $this->createProvider(new \Symfony\Component\HttpClient\MockHttpClient(
            function ($method, $url, $options) {
                self::assertArrayHasKey('query', $options);
                $query = $options['query'];
                self::assertArrayHasKey('page', $query);
                $page = $query['page'];
                $path = str_replace(self::BASE_URI, '', $url);
                $path = explode('?', $path)[0];
                $data = HttpClientFixture::getPagedData('TolgeeApi/GetLanguagesTest', $path, $method, $page);
                return new \Symfony\Component\HttpClient\Response\MockResponse($data);
            },
            self::BASE_URI
        ));
        
        $data = iterator_to_array($this->invokeTolgeeProviderMethod($provider,'getLanguages'));

        self::assertEquals([
            1000025006 => "de",
            1000000001 => "en",
            1000006002 => "it",
            1000025007 => "sk"
        ], $data);
    }
}
