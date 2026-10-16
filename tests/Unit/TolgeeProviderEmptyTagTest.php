<?php

declare(strict_types=1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Unit;

use Netlogix\SymfonyTolgeeTranslationProvider\TolgeeProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Translation\Loader\ArrayLoader;

class TolgeeProviderEmptyTagTest extends TestCase
{
    private const BASE_URI = 'https://tolgee.local/v2/projects/1/';

    public function testTaggedKeysAreReadAsEmptyStrings(): void
    {
        $requestedQueries = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requestedQueries) {
            $requestedQueries[] = $options['query'];

            if (isset($options['query']['filterTagIn'])) {
                return new MockResponse(self::createZip([
                    'messages/de.json' => ['header' => ['supportInfo' => null, 'withText' => 'Text in Tolgee']],
                    'messages/en.json' => ['header' => ['supportInfo' => null, 'withText' => 'Text in Tolgee']],
                ]));
            }

            return new MockResponse(self::createZip([
                'messages/de.json' => ['header' => ['withText' => 'Text in Tolgee', 'untagged' => 'Bleibt']],
            ]));
        }, self::BASE_URI);

        $translatorBag = $this->createProvider($client, 'empty')->read(['messages'], ['de', 'en']);

        self::assertSame(
            [
                'header.withText' => '',
                'header.untagged' => 'Bleibt',
                'header.supportInfo' => '',
            ],
            $translatorBag->getCatalogue('de')->all('messages')
        );
        self::assertSame(
            [
                'header.supportInfo' => '',
                'header.withText' => '',
            ],
            $translatorBag->getCatalogue('en')->all('messages')
        );

        self::assertCount(2, $requestedQueries);
        self::assertSame('empty', $requestedQueries[1]['filterTagIn']);
        self::assertSame('UNTRANSLATED,TRANSLATED,REVIEWED', $requestedQueries[1]['filterState']);
    }

    public function testEmptyTagOverridesConfiguredFilterState(): void
    {
        $requestedQueries = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requestedQueries) {
            $requestedQueries[] = $options['query'];

            return new MockResponse(self::createZip([]));
        }, self::BASE_URI);

        $this->createProvider($client, 'empty', 'REVIEWED')->read(['messages'], ['de']);

        self::assertSame('REVIEWED', $requestedQueries[0]['filterState']);
        self::assertArrayNotHasKey('filterTagIn', $requestedQueries[0]);
        self::assertSame('UNTRANSLATED,TRANSLATED,REVIEWED', $requestedQueries[1]['filterState']);
    }

    public function testNoAdditionalRequestWithoutEmptyTag(): void
    {
        $requestCount = 0;
        $client = new MockHttpClient(function () use (&$requestCount) {
            $requestCount++;

            return new MockResponse(self::createZip(['messages/de.json' => ['foo' => 'bar']]));
        }, self::BASE_URI);

        $translatorBag = $this->createProvider($client, null)->read(['messages'], ['de']);

        self::assertSame(1, $requestCount);
        self::assertSame(['foo' => 'bar'], $translatorBag->getCatalogue('de')->all('messages'));
    }

    public function testMissingTaggedKeysAreLoggedAsDebug(): void
    {
        $client = new MockHttpClient(function () {
            return new MockResponse(json_encode(['code' => 'no_exported_result']), ['http_code' => 400]);
        }, self::BASE_URI);

        $loggedLevels = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('log')->willReturnCallback(function ($level, $message) use (&$loggedLevels) {
            $loggedLevels[] = [$level, $message];
        });
        $logger->expects(self::never())->method('warning');

        $this->createProvider($client, 'empty', null, $logger)->read(['messages'], ['de']);

        self::assertSame(
            [
                [LogLevel::WARNING, 'No exported result from export API'],
                [LogLevel::DEBUG, 'No exported result from export API'],
            ],
            $loggedLevels
        );
    }

    private function createProvider(
        MockHttpClient $client,
        ?string $emptyTag,
        ?string $filterState = null,
        ?LoggerInterface $logger = null
    ): TolgeeProvider {
        return new TolgeeProvider(
            $client,
            new ArrayLoader(),
            $logger ?? new NullLogger(),
            'de',
            'tolgee.local',
            $filterState,
            $emptyTag
        );
    }

    private static function createZip(array $files): string
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'tolgee_test_zip_');
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, json_encode($content));
        }
        if ($files === []) {
            $zip->addEmptyDir('empty');
        }
        $zip->close();

        $content = file_get_contents($zipFile);
        unlink($zipFile);

        return $content;
    }
}
