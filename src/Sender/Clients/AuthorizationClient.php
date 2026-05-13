<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Clients;

use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use MTZ\Toolkit\Sender\Data\AuthorizationResult;
use MTZ\Toolkit\Sender\Exceptions\ConnectionException;
use MTZ\Toolkit\Sender\Exceptions\InvalidAccessKeyException;
use MTZ\Toolkit\Sender\Support\NativeSoapClientFactory;
use SoapFault;

final class AuthorizationClient
{
    private ?object $lastResponse = null;

    public function __construct(
        private readonly SenderConfig $config,
        private readonly \MTZ\Toolkit\Sender\Services\ResponseParser $responseParser = new \MTZ\Toolkit\Sender\Services\ResponseParser(),
        private readonly SoapClientFactoryInterface $soapClientFactory = new NativeSoapClientFactory(),
        private readonly \MTZ\Toolkit\Sender\Contracts\SleeperInterface $sleeper = new \MTZ\Toolkit\Sender\Support\NativeSleeper(),
    )
    {
    }

    public function authorize(string $accessKey): AuthorizationResult
    {
        $this->assertAccessKey($accessKey);

        $attempts = 0;

        try
        {
            $client = $this->soapClientFactory->make(
                $this->config->authorizationWsdl(),
                $this->config->normalizedSoapOptions()
            );

            while ($attempts < $this->config->maxAttempts)
            {
                $attempts++;

                try
                {
                    $this->lastResponse = $client->autorizacionComprobante([
                        'claveAccesoComprobante' => $accessKey
                    ]);

                    $status = $this->responseParser->authorizationStatus($this->lastResponse);
                    $messages = $this->responseParser->authorizationMessages($this->lastResponse);

                    if ($this->responseParser->isAuthorizationSuccessful($this->lastResponse))
                    {
                        return AuthorizationResult::success(
                            status: $status,
                            authorizedDocument: $this->responseParser->authorizedDocument($this->lastResponse),
                            messages: $messages,
                            attempts: $attempts,
                            rawResponse: $this->lastResponse,
                        );
                    }

                    if ($attempts < $this->config->maxAttempts)
                    {
                        $this->sleeper->sleep($this->config->retryDelay);
                        continue;
                    }

                    return AuthorizationResult::failure(
                        status: $status,
                        error: $this->messagesToString($messages),
                        messages: $messages,
                        attempts: $attempts,
                        rawResponse: $this->lastResponse,
                    );
                }
                catch (SoapFault $exception)
                {
                    if ($attempts >= $this->config->maxAttempts)
                    {
                        return AuthorizationResult::failure(
                            status: null,
                            error: ConnectionException::soap($exception->getMessage())->getMessage(),
                            attempts: $attempts,
                        );
                    }

                    $this->sleeper->sleep($this->config->retryDelay);
                }
            }

            return AuthorizationResult::failure(
                status: null,
                error: 'Max attempts reached, no response received.',
                attempts: $attempts,
            );
        }
        catch (SoapFault $exception)
        {
            return AuthorizationResult::failure(
                status: null,
                error: ConnectionException::soap($exception->getMessage())->getMessage(),
                attempts: $attempts,
            );
        }
    }

    public function lastResponse(): ?object
    {
        return $this->lastResponse;
    }

    private function assertAccessKey(string $accessKey): void
    {
        if (! preg_match('/^\d{49}$/', $accessKey))
        {
            throw InvalidAccessKeyException::make($accessKey);
        }
    }

    public function messagesToString(array $messages): string
    {
        if ($messages === [])
        {
            return 'WebService SRI not responding or return an empty response';
        }

        return implode(
            "\n", array_map(
                static fn($message): string => $message->toString(),
                $messages
            )
        );
    }
}