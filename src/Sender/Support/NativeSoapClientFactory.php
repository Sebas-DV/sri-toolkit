<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Support;

use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use SoapClient;

/**
 * Native implementation of the SOAP client factory using PHP's built-in SoapClient.
 *
 * Creates a new SoapClient instance with the provided WSDL URL and options.
 * This is the default factory used by the sender module.
 */
final class NativeSoapClientFactory implements SoapClientFactoryInterface
{
    /**
     * Creates a new SOAP client instance.
     *
     * @param string $wsdl The WSDL URL for the SOAP service.
     * @param array<string, mixed> $options SOAP client options.
     * @return SoapClient The configured SOAP client.
     */
    public function make(string $wsdl, array $options = []): SoapClient
    {
        return new SoapClient($wsdl, $options);
    }
}
