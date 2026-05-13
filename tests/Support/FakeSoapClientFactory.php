<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support;

use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use SoapClient;

final class FakeSoapClientFactory implements SoapClientFactoryInterface
{
    public array $createWsdls = [];
    public array $createOptions = [];

    public function __construct(
        private readonly FakeSoapClient $soapClient,
    )
    {
    }

    public function make(string $wsdl, array $options = []): SoapClient
    {
        $this->createWsdls[] = $wsdl;
        $this->createOptions[] = $options;

        return $this->soapClient;
    }
}