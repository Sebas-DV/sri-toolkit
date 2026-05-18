<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Contracts;

use SoapClient;

interface SoapClientFactoryInterface
{
    public function make(string $wsdl, array $options = []): SoapClient;
}
