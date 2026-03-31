<?php

declare(strict_types=1);

namespace Netlogix\SymfonyTolgeeTranslationProvider;

use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Exception\IncompleteDsnException;
use Symfony\Component\Translation\Exception\UnsupportedSchemeException;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Provider\AbstractProviderFactory;
use Symfony\Component\Translation\Provider\Dsn;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TolgeeProviderFactory extends AbstractProviderFactory
{
    /** @var ArrayLoader */
    private $loader;

    /** @var HttpClientInterface */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $defaultLocale;


    public function __construct(
        HttpClientInterface $client,
        LoggerInterface     $logger,
        string              $defaultLocale,
        ArrayLoader         $loader
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->defaultLocale = $defaultLocale;
        $this->loader = $loader;
    }

    /**
     * @return TolgeeProvider
     */
    public function create(Dsn $dsn): ProviderInterface
    {
        if ($this->supports($dsn) === false) {
            throw new UnsupportedSchemeException($dsn, 'tolgee', $this->getSupportedSchemes());
        }

        $client = $this->client->withOptions([
            'base_uri' => sprintf(
                '%s://%s%s/v2/projects/%d/',
                $dsn->getScheme() === 'tolgees' ? 'https' : 'http',
                $dsn->getHost(),
                ($dsn->getPort() ? ":".$dsn->getPort() : ''),
                (int)$this->getUser($dsn)
            ),
            'headers' => [
                'X-Api-Key' => $this->getPassword($dsn),
            ],
        ]);

        if (
            ($filterState = $dsn->getPath() ? trim($dsn->getPath(), '/') : NULL)
            && !in_array($filterState, TolgeeProvider::ALLOWED_FILTER_STATES)
        ) {
            throw new IncompleteDsnException('Filter state is not valid. Allowed values are: ' . implode(', ', TolgeeProvider::ALLOWED_FILTER_STATES));
        }

        return new TolgeeProvider(
            $client,
            $this->loader,
            $this->logger,
            $this->defaultLocale,
            $dsn->getHost() . ($dsn->getPort() ? ':' . $dsn->getPort() : ''),
            $filterState
        );
    }

    protected function getSupportedSchemes(): array
    {
        return ['tolgee', 'tolgees'];
    }
}
