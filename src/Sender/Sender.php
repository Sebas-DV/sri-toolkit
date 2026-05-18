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
use MTZ\Toolkit\Sender\Exceptions\InvalidAccessKeyException;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Sender\Support\NativeSleeper;
use MTZ\Toolkit\Sender\Support\NativeSoapClientFactory;

/**
 * Primary entry point for sending electronic documents to the SRI.
 *
 * Orchestrates the full send pipeline: validates the signed XML via the
 * reception web service, waits a configurable delay, then authorizes the
 * document via the authorization web service.
 */
final readonly class Sender
{
    /**
     * @var ReceptionClient Client for the SRI reception web service.
     */
    private ReceptionClient $receptionClient;

    /**
     * @var AuthorizationClient Client for the SRI authorization web service.
     */
    private AuthorizationClient $authorizationClient;

    /**
     * @param SenderConfig $config The sender configuration.
     * @param ResponseParser|null $responseParser Optional custom response parser.
     * @param SoapClientFactoryInterface|null $soapClientFactory Optional custom SOAP client factory.
     * @param SleeperInterface $sleeper Provides sleep functionality for delays.
     */
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

    /**
     * Validates a signed XML document with the SRI reception web service.
     *
     * @param string $signedXml The signed XML document to validate.
     * @return ReceptionResult The result of the reception validation.
     */
    public function validate(string $signedXml): ReceptionResult
    {
        return $this->receptionClient->validate($signedXml);
    }

    /**
     * Authorizes a document with the SRI using its access key.
     *
     * @param string $accessKey The 49-digit SRI access key.
     * @return AuthorizationResult The result of the authorization attempt.
     * @throws InvalidAccessKeyException If the access key format is invalid.
     */
    public function authorize(string $accessKey): AuthorizationResult
    {
        return $this->authorizationClient->authorize($accessKey);
    }

    /**
     * Executes the full send pipeline: validates and then authorizes a document.
     *
     * If validation fails, the process stops and returns a failure. Otherwise,
     * it waits for the configured send delay and proceeds with authorization.
     *
     * @param string $accessKey The 49-digit SRI access key.
     * @param string $signedXml The signed XML document to send.
     * @return SendResult The aggregated result of both reception and authorization.
     * @throws InvalidAccessKeyException If the access key format is invalid.
     */
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
