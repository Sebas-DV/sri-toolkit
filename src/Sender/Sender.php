<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender;

use MTZ\Toolkit\Sender\Clients\AuthorizationClient;
use MTZ\Toolkit\Sender\Clients\ReceptionClient;
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Contracts\SleeperInterface;
use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use MTZ\Toolkit\Sender\Data\AuthorizationResult;
use MTZ\Toolkit\Sender\Data\ReceptionResult;
use MTZ\Toolkit\Sender\Data\SendResult;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Sender\Support\NativeSleeper;
use MTZ\Toolkit\Sender\Support\NativeSoapClientFactory;

final readonly class Sender
{
    private ReceptionClient $receptionClient;
    private AuthorizationClient $authorizationClient;

    public function __construct(
        private SenderConfig $config = new SenderConfig(),
        ?ResponseParser $responseParser = null,
        ?SoapClientFactoryInterface $soapClientFactory = null,
        private SleeperInterface $sleeper = new NativeSleeper(),
    ) {
        $responseParser ??= new ResponseParser();
        $soapClientFactory ??= new NativeSoapClientFactory();

        $this->receptionClient = new ReceptionClient(
            config: $this->config,
            responseParser: $responseParser,
            soapClientFactory: $soapClientFactory,
        );

        $this->authorizationClient = new AuthorizationClient(
            config: $this->config,
            responseParser: $responseParser,
            soapClientFactory: $soapClientFactory,
            sleeper: $this->sleeper,
        );
    }

    public function validate(string $signedXml): ReceptionResult
    {
        return $this->receptionClient->validate($signedXml);
    }

    public function authorize(string $accessKey): AuthorizationResult
    {
        return $this->authorizationClient->authorize($accessKey);
    }

    public function send(string $accessKey, string $signedXml): SendResult
    {
        $reception = $this->validate($signedXml);

        if (! $reception->success)
        {
            return SendResult::failure(
                error: $reception->error ?? 'An error occurred while validating the XML, voucher not received.',
                receptionResult: $reception,
                authorizationResult: null,
            );
        }

        $this->sleeper->sleep($this->config->sendDelay);

        $authorization = $this->authorize($accessKey);

        if (! $authorization->success)
        {
            return SendResult::failure(
                error: $authorization->error ?? 'An error occurred while authorizing the XML, voucher not sent.',
                receptionResult: $reception,
                authorizationResult: $authorization,
            );
        }

        return SendResult::success(
            receptionStatus: $reception,
            authorizationResult: $authorization,
        );
    }
}
