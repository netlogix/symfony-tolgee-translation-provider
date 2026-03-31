<?php

declare(strict_types=1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Functional;

use Netlogix\SymfonyTolgeeTranslationProvider\Test\Fixtures\HttpClientFixture;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Provider\TranslationProviderCollection;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\Response\MockResponse;

class TolgeeProviderTest extends KernelTestCase
{
    private $currentKernel;
    /**
     * @var MockHttpClient|HttpClientInterface
     */
    private $client;
    private static $fs = null;

    public function setUp(): void
    {
        $this->currentKernel = self::bootKernel([
            'environment' => 'test',
            'debug'       => false,
        ]);

        $this->client = new MockHttpClient();
        $this->currentKernel->getContainer()->set('http_client', $this->client);

        foreach ([
            $this->currentKernel->getContainer()->getParameter('translator.default_path')
        ] as $dir) {
            if (self::getFs()->exists($dir)) {
                self::getFs()->remove($dir);
            }
        }
    }

    public function testReadTranslations(): void
    {

        $this->client->setResponseFactory(function ($method, $url, $options) {
            $fixture = 'TolgeeApi/Functional/Read';
            if ($method == 'GET' && strpos($url, '/used-namespaces') > 1) {
                $data = HttpClientFixture::getData($fixture, 'used-namespaces', $method);
            } elseif ($method == 'GET' && strpos($url, '/languages') > 1) {
                self::assertArrayHasKey('query', $options);
                $query = $options['query'];
                $page = $query['page'];
                $data = HttpClientFixture::getPagedData($fixture, 'languages', $method, $page);
            } elseif ($method == 'GET' && strpos($url, '/export') > 1) {
                self::assertArrayHasKey('query', $options);
                $query = $options['query'];
                $filterNamespace = $query['filterNamespace'];
                $languages = $query['languages'];
                $data = HttpClientFixture::getExportZip($fixture, $filterNamespace, $languages);
            }
            else {
                throw new \RuntimeException(sprintf('Unhandled request: %s %s', $method, $url));
            }

            return new MockResponse($data ?? '');
        });

        $tb = $this->getTolgeeProvider()->read([], []);
        $translations = [];
        foreach ($tb->getCatalogues() as $cataloge) {
            $locale = $cataloge->getLocale();
            foreach ($cataloge->getDomains() as $domain) {
                $translations[$domain][$locale] = $cataloge->all($domain);
            }
        }

        $excpect = array(
            'validation' =>  array(
                'de' => array(
                    'error' => 'fehler',
                    'success' => 'erfolgreich',
                ),
                'en' =>  array(
                    'error' => 'error',
                    'success' => 'success',
                ),
            ),
            'message' => array(
                'de' => array(
                    'wellcome' => 'Willkommen',
                ),
                'en' => array(
                    'wellcome' => 'Wellcome',
                ),
            )
        );

        $this->assertSame($excpect, $translations);
    }

    public function testWrite(): void
    {
        $expectedRequests = [
            'GET::languages' => 1,
            'DELETE::import' => 1,
            'POST::languages' => 1,
            'POST::import' => 1,
            'PUT::select-namespace' => 2,
            'PUT::apply' => 1,
        ];

        $this->client->setResponseFactory(function ($method, $url, $options) use (&$expectedRequests) {
            $fixture = 'TolgeeApi/Functional/Write';

            if ($method == 'GET' && strpos($url, '/languages') > 1) {
                self::assertArrayHasKey('query', $options);
                $query = $options['query'];
                $page = $query['page'];
                $data = HttpClientFixture::getPagedData($fixture, 'languages', $method, $page);
                $expectedRequests['GET::languages']--;
            } elseif ($method == 'DELETE' && strpos($url, '/import') > 1) {
                $expectedRequests['DELETE::import']--;
            } elseif ($method == 'POST' && strpos($url, '/languages') > 1) {
                $data = HttpClientFixture::getData($fixture, 'languages', $method);
                $expectedRequests['POST::languages']--;
            } elseif ($method == 'POST' && strpos($url, '/import') > 1) {
                self::assertArrayHasKey('query', $options);
                $body = $options['body'];
                $parts = [];
                $parent = null;
                $fileNames = [];

                while ('' !== $data = $body()) {
                    if (preg_match(
                        '/^Content-Disposition:.*filename="?(?<filename>[^" ]*)/m',
                        $data,
                        $matches
                    )) {
                        $fileNames[] = $matches['filename'];
                    } elseif (
                        $parent === "\r\n"
                        && strpos($data, '--') === false
                    ) {
                        $parts = array_unique(array_merge(array_keys(json_decode($data, true)), $parts));
                    }
                    $parent = $data;
                }
                $fileNames = array_map(fn ($fn) => str_replace('.json', '', $fn), $fileNames);
                sort($fileNames);
                sort($parts);

                $data = HttpClientFixture::getData(
                    $fixture,
                    join('.', array_merge(['import'], $parts, $fileNames)),
                    $method
                );
                $expectedRequests['POST::import']--;
            } elseif ($method == 'PUT' && strpos($url, '/select-namespace') > 1) {
                $expectedRequests['PUT::select-namespace']--;
            } elseif ($method == 'PUT' && strpos($url, '/apply') > 1) {
                $expectedRequests['PUT::apply']--;
            } else {
                dd([
                    'method' => $method,
                    'url' => $url,
                    'options' => $options
                ]);
            }

            return new MockResponse($data ?? '');
        });

        $translatorBag = new TranslatorBag();
        $translatorBag->addCatalogue(new MessageCatalogue('en', [
            'test' => [
                'foo' => 'Foo en'
            ]
        ]));
        $translatorBag->addCatalogue(new MessageCatalogue('de', [
            'test' => [
                'foo' => 'Foo de'
            ]
        ]));
        $translatorBag->addCatalogue(new MessageCatalogue('fr', [
            'test' => [
                'foo' => 'Foo fr'
            ]
        ]));

        $this->getTolgeeProvider()->write($translatorBag);

        foreach ($expectedRequests as $key => $value) {
            self::assertEquals(0, $value, $key . ' should be called');
        }
    }

    public function testDelete()
    {
        $expectedRequests = [
            'GET::translations/select-all' => 1,
            'DELETE::keys' => 1,
        ];

        $translatorBag = new TranslatorBag();
        $translatorBag->addCatalogue(new MessageCatalogue('en', [
            'test' => [
                'foo' => 'Foo en',
                'bar' => 'Bar en'
            ]
        ]));

        $this->client->setResponseFactory(function ($method, $url, $options) use (&$expectedRequests) {
            $fixture = 'TolgeeApi/Functional/Delete';
            if ($method == 'GET' && strpos($url, '/translations/select-all') > 1) {
                $data = HttpClientFixture::getData($fixture, 'translations_select-all', $method);
                $expectedRequests['GET::translations/select-all']--;
            } elseif ($method == 'DELETE' && strpos($url, '/keys') > 1) {
                $body = json_decode($options['body'], true);
                $this->assertArrayHasKey('ids', $body);
                $this->assertContains(1000008002, $body['ids']);
                $expectedRequests['DELETE::keys']--;
            }

            return new MockResponse($data ?? '');
        });

        $this->getTolgeeProvider()->delete($translatorBag);

        foreach ($expectedRequests as $key => $value) {
            self::assertEquals(0, $value, $key . ' should be called');
        }
    }

    protected function getTolgeeProvider()
    {
        /** @var TranslationProviderCollection $translationProviderCollection */
        $translationProviderCollection = $this->getContainer()->get('translation.provider_collection');

        return $translationProviderCollection->get('tolgee');
    }

    private static function getFs(): Filesystem
    {
        return self::$fs ?? self::$fs = new Filesystem();
    }
}
