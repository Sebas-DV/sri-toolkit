<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Contracts;

use SoapClient;

/**
 * Contract for a factory that creates SOAP client instances.
 *
 * Implementations should return a configured SoapClient for the given WSDL
 * and options, allowing for dependency injection and testability.
 */
interface SoapClientFactoryInterface
{
    /**
     * Creates a new SOAP client instance.
     *
     * @param string $wsdl The WSDL URL for the SOAP service.
     * @param array<string, mixed> $options SOAP client options.
     * @return SoapClient The configured SOAP client.
     */
    public function make(string $wsdl, array $options = []): SoapClient;
}
