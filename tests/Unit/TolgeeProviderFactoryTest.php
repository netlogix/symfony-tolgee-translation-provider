<?php

declare(strict_types=1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Netlogix\SymfonyTolgeeTranslationProvider\TolgeeProviderFactory;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Translation\Exception\IncompleteDsnException;
use Symfony\Component\Translation\Exception\UnsupportedSchemeException;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Component\Translation\Provider\Dsn;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TolgeeProviderFactoryTest extends TestCase
{
    public static function createDataProvider(): iterable
    {
        yield "http" => [
            'tolgee://1:123@localhost',
            'http://localhost/v2/projects/1/',
            '123'
        ];

        yield "https" => [
            'tolgees://2:123@localhost',
            'https://localhost/v2/projects/2/',
            '123'
        ];

        yield "http with port" => [
            'tolgee://3:123@localhost:8080',
            'http://localhost:8080/v2/projects/3/',
            '123'
        ];

        yield "https with port" => [
            'tolgees://4:123@localhost:8080',
            'https://localhost:8080/v2/projects/4/',
            '123'
        ];

        yield "with filter" => [
            'tolgees://4:123@localhost/TRANSLATED',
            'https://localhost/v2/projects/4/',
            '123'
        ];

        yield "with unsupported filter" => [
            'tolgees://4:123@localhost/FOO_BAR',
            'https://localhost/v2/projects/4/',
            '123',
            IncompleteDsnException::class
        ];

        yield "unsupportet schema" => [
            'http://4:123@localhost:8080',
            null,
            null,
            UnsupportedSchemeException::class
        ];
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        string $dsn,
        ?string $base_uri,
        ?string $apiKey,
        ?string $exeption = null
    ) {
        $client = Mockery::mock(HttpClientInterface::class);
        $subject = $this->getFactory($client);
        if ($exeption !== null) {
            $this->expectException($exeption);
        }

        if ($base_uri !== Null && $apiKey !== Null) {
            $client->shouldReceive('withOptions')
                ->once()
                ->with(Mockery::on(function (array $options) use ($base_uri, $apiKey) {
                    $this->assertEquals($base_uri, ($options['base_uri'] ?? null));
                    $this->assertEquals($apiKey, ($options['headers']['X-Api-Key'] ?? null));
                    return true;
                }))->andReturnSelf();
        }

        $this->assertInstanceOf(ProviderInterface::class, $subject->create(new Dsn($dsn)));
    }

    private function getFactory(HttpClientInterface $client): TolgeeProviderFactory
    {
        return new TolgeeProviderFactory(
            $client,
            $this->createMock(LoggerInterface::class),
            'en',
            new ArrayLoader()
        );
    }
}
