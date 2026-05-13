<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support;

use SoapClient;
use stdClass;
use Throwable;

final class FakeSoapClient extends SoapClient
{
    public array $receivedXmls = [];
    public array $authorizedAccessKeys = [];

    /**
     * @param array<int, object|Throwable> $receptionResponses
     * @param array<int, object|Throwable> $authorizationResponses
     */
    public function __construct(
        private array $receptionResponses = [],
        private array $authorizationResponses = [],
    )
    {
    }

    public function validarComprobante(array $parameters): object
    {
        $this->receivedXmls[] = $parameters['xml'] ?? null;

        $response = array_shift($this->receptionResponses);

        if ($response instanceof Throwable)
        {
            throw $response;
        }

        return $response ?? new stdClass();
    }

    public function autorizarComprobante(array $parameters): object
    {
        $this->authorizedAccessKeys[] = $parameters['claveAccesoComprobante'] ?? null;

        $response = array_shift($this->authorizationResponses);

        if ($response instanceof Throwable)
        {
            throw $response;
        }

        return $response ?? new stdClass();
    }
}