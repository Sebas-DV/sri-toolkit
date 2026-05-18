<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support;

use Exception;
use SoapClient;
use stdClass;
use Throwable;

final class FakeSoapClient extends SoapClient
{
    public array $receivedXmls = [];
    public array $authorizedAccessKeys = [];

    public function __construct(
        private array $receptionResponses = [],
        private array $authorizationResponses = [],
    ) {
    }

    /**
     * @throws Throwable
     */
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

    /**
     * @throws Throwable
     */
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

    /**
     * @throws Throwable
     */
    public function __call(string $name, array $args): mixed
    {
        return $this->dispatchSoapCall($name, $args);
    }

    /**
     * @throws Throwable
     */
    public function __soapCall(string $name, array $args, ?array $options = null, $inputHeaders = null, &$outputHeaders = null): mixed
    {
        return $this->dispatchSoapCall($name, $args);
    }

    /**
     * @throws Throwable
     */
    private function dispatchSoapCall(string $name, array $args): object
    {
        $parameters = $this->extractParameters($args);

        return match ($name)
        {
            'validarComprobante' => $this->validarComprobante($parameters),
            'autorizacionComprobante' => $this->autorizarComprobante($parameters),
            default => throw new Exception("Method {$name} not found"),
        };
    }

    /**
     * @throws Throwable
     */
    private function handleReception(array $parameters): object
    {
        $this->receivedXmls[] = $parameters['xml'] ?? null;

        $response = array_shift($this->receptionResponses);

        if ($response instanceof Throwable)
        {
            throw $response;
        }

        return $response ?? new stdClass();
    }

    /**
     * @throws Throwable
     */
    private function handleAuthorization(array $parameters): object
    {
        $this->authorizedAccessKeys[] = $parameters['claveAccesoComprobante'] ?? null;

        $response = array_shift($this->authorizationResponses);

        if ($response instanceof Throwable)
        {
            throw $response;
        }

        return $response ?? new stdClass();
    }

    private function extractParameters(array $args): array
    {
        if (isset($args[0]) && is_array($args[0]))
        {
            return $args[0];
        }

        return $args;
    }

}
