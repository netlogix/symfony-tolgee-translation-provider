<?php
declare(strict_types=1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Symfony;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Component\Translation\Test\ProviderTestCase;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Netlogix\SymfonyTolgeeTranslationProvider\TolgeeProvider;

class TolgeeProviderTest extends ProviderTestCase
{
    protected function getLoader(): LoaderInterface
    {
        return $this->loader ?? $this->loader = new ArrayLoader();
    }

    public static function createProvider(HttpClientInterface $client, LoaderInterface $loader, LoggerInterface $logger, string $defaultLocale, string $endpoint): ProviderInterface
    {
        return new TolgeeProvider($client, $loader, $logger, $defaultLocale, $endpoint);
    }

    public static  function toStringProvider(): iterable
    {
        $loader = new ArrayLoader();
        yield 'app.tolgee.io' => [
            self::createProvider(
                self::getHttpClient(),
                $loader,
                new NullLogger(),
                'en',
                'app.tolgee.io'
            ),
            'tolgee://app.tolgee.io',
        ];

        yield 'local.dev' => [
            self::createProvider(
                self::getHttpClient(),
                $loader,
                new NullLogger(),
                'en',
                'local.dev'
            ),
            'tolgee://local.dev',
        ];

        yield 'example.com:99' => [
            self::createProvider(
                self::getHttpClient(),
                $loader,
                new NullLogger(),
                'en',
                'example.com:99'
            ),
            'tolgee://example.com:99',
        ];
    }

    public static function getResponsesForOneLocaleAndOneDomain(): \Generator
    {
        $arrayLoader = new ArrayLoader();

        $expectedTranslatorBagEn = new TranslatorBag();
        $expectedTranslatorBagEn->addCatalogue($arrayLoader->load([
            'index.hello' => 'Hello',
            'index.greetings' => 'Welcome, {firstname}!',
        ], 'en'));

        yield [
            'en', 'messages', [
                "index" => [
                    "hello" => "Hello",
                    "greetings" => "Welcome, {firstname}!"
                ]
            ],
            $expectedTranslatorBagEn,
        ];
    }

    /**
     * @dataProvider getResponsesForOneLocaleAndOneDomain
     */
    public function testReadForOneLocaleAndOneDomain(string $locale, string $domain, array $responseContent, TranslatorBag $expectedTranslatorBag)
    {
        $response = function (string $method, string $url, array $options = []) use ($locale, $domain, $responseContent): ResponseInterface {
            $this->assertSame('GET', $method);

            // Check URL contains all required parameters (order may vary)
            $this->assertStringContainsString('filterNamespace=' . $domain, $url);
            $this->assertStringContainsString('languages=' . $locale, $url);
            $this->assertStringContainsString('format=JSON', $url);
            $this->assertStringContainsString('zip=1', $url);

            $zip = new \ZipArchive();
            $tmpZip = tempnam(sys_get_temp_dir(), 'test_export_') . ".zip";
            $zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString(sprintf('%s/%s.json', $domain, $locale), json_encode($responseContent));
            $zip->close();
            $content = file_get_contents($tmpZip);
            unlink($tmpZip);
            return new MockResponse($content);
        };

        $client = self::getHttpClient($response);
        $provider = self::createProvider($client, $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'app.tolgee.io');
        $translatorBag = $provider->read([$domain], [$locale]);
        $this->assertEquals($expectedTranslatorBag->getCatalogue($locale)->all($domain), $translatorBag->getCatalogue($locale)->all($domain));
    }


    public static function getResponsesForManyLocalesAndManyDomains(): \Generator
    {
        $arrayLoader = new ArrayLoader();

        $expectedTranslatorBag = new TranslatorBag();
        $expectedTranslatorBag->addCatalogue($arrayLoader->load([
            'index.hello' => 'Hello',
            'index.greetings' => 'Welcome, {firstname}!',
        ], 'en'));
        $expectedTranslatorBag->addCatalogue($arrayLoader->load([
            'index.hello' => 'Bonjour',
            'index.greetings' => 'Bienvenue, {firstname} !',
        ], 'fr'));
        $expectedTranslatorBag->addCatalogue($arrayLoader->load([
            'firstname.error' => 'Firstname must contains only letters.',
            'lastname.error' => 'Lastname must contains only letters.',
        ], 'en', 'validators'));
        $expectedTranslatorBag->addCatalogue($arrayLoader->load([
            'firstname.error' => 'Le prénom ne peut contenir que des lettres.',
            'lastname.error' => 'Le nom de famille ne peut contenir que des lettres.',
        ], 'fr', 'validators'));

        yield [
            ['en', 'fr'],
            ['messages', 'validators'],
            [
                'messages' => [
                    'en' => [
                        "index" => [
                            "hello" => "Hello",
                            "greetings" => "Welcome, {firstname}!"
                        ],
                    ],
                    'fr' => [
                        "index" => [
                            "hello" => "Bonjour",
                            "greetings" => "Bienvenue, {firstname} !"
                        ]
                    ]
                ],
                'validators' => [
                    'en' => [
                        'firstname' => ['error' => 'Firstname must contains only letters.'],
                        'lastname' => ['error' => 'Lastname must contains only letters.']
                    ],
                    'fr' => [
                        'firstname' => ['error' => 'Le prénom ne peut contenir que des lettres.'],
                        'lastname' => ['error' => 'Le nom de famille ne peut contenir que des lettres.']
                    ]
                ]
            ],
            $expectedTranslatorBag,
        ];
    }

    /**
     * @dataProvider getResponsesForManyLocalesAndManyDomains
     */
    public function testReadForManyLocalesAndManyDomains(array $locales, array $domains, array $responseContents, TranslatorBag $expectedTranslatorBag)
    {
        $response = function (string $method, string $url, array $options = []) use ($locales, $domains, $responseContents): ResponseInterface {
            $this->assertSame('GET', $method);
            $query = [];
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            self::assertArrayHasKey('filterNamespace', $query);
            self::assertArrayHasKey('languages', $query);
            $domain = $query['filterNamespace'];
            $locale = $query['languages'];
            self::assertEquals($domain, join(",", $domains));
            self::assertEquals($locale, join(",", $locales));
            $zip = new \ZipArchive();
            $tmpZip = tempnam(sys_get_temp_dir(), 'test_export_') . ".zip";
            $zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            foreach ($domains as $domain) {
                foreach ($locales as $locale) {
                   $zip->addFromString(sprintf('%s/%s.json', $domain, $locale), json_encode($responseContents[$domain][$locale]));
               }
            }
            $zip->close();
            $content = file_get_contents($tmpZip);
            unlink($tmpZip);
            return new MockResponse($content);
        };

        $client = self::getHttpClient($response);
        $provider = self::createProvider($client, $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'app.tolgee.io');
        $translatorBag = $provider->read($domains, $locales);
        foreach ($domains as $domain) {
            foreach ($locales as $locale) {
              $this->assertEquals($expectedTranslatorBag->getCatalogue($locale)->all($domain), $translatorBag->getCatalogue($locale)->all($domain));
            }
        }
    }

    private static function getHttpClient($response = null): MockHttpClient
    {
        return (new MockHttpClient($response))->withOptions([
            'base_uri' => 'https://app.tolgee.io/v2/projects/1337/',
            'headers' => ['X-Api-Key' => 'API_KEY'],
        ]);
    }
}
