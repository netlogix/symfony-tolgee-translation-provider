<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Exception;

use Exception;
use Throwable;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TolgeeException extends Exception
{
    private ?ResponseInterface $response;

    public function __construct(
        string $message,
        int $code = 0,
        ?ResponseInterface $response = null,
        ?Throwable $previous = null
    ) {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
