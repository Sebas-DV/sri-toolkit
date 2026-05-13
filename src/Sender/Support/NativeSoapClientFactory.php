<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Support;

use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use SoapClient;

final class NativeSoapClientFactory implements SoapClientFactoryInterface
{
    public function make(string $wsdl, array $options = []): SoapClient
    {
        return new SoapClient($wsdl, $options);
    }
}