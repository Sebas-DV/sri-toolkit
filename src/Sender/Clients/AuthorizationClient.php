<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Clients;

use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Contracts\SleeperInterface;
use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use MTZ\Toolkit\Sender\Data\AuthorizationResult;
use MTZ\Toolkit\Sender\Data\Message;
use MTZ\Toolkit\Sender\Exceptions\ConnectionException;
use MTZ\Toolkit\Sender\Exceptions\InvalidAccessKeyException;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Sender\Support\NativeSleeper;
use MTZ\Toolkit\Sender\Support\NativeSoapClientFactory;
use SoapFault;

/**
 * Handles authorization of electronic documents with the SRI (Ecuadorian tax authority).
 *
 * This client sends the access key to the SRI authorization web service, parses
 * the SOAP response, and returns the authorization result. It supports retry logic
 * with configurable attempts and delays.
 */
final class AuthorizationClient
{
    /**
     * @var object|null The raw SOAP response from the last authorization request.
     */
    private ?object $lastResponse = null;

    /**
     * @param SenderConfig $config The sender configuration for WSDL URLs and retry settings.
     * @param ResponseParser $responseParser Service that parses SOAP responses from the SRI.
     * @param SoapClientFactoryInterface $soapClientFactory Factory for creating SOAP clients.
     * @param SleeperInterface $sleeper Provides sleep functionality for retry delays.
     */
    public function __construct(
        private readonly SenderConfig $config,
        private readonly ResponseParser $responseParser = new ResponseParser(),
        private readonly SoapClientFactoryInterface $soapClientFactory = new NativeSoapClientFactory(),
        private readonly SleeperInterface $sleeper = new NativeSleeper(),
    ) {
    }

    /**
     * Sends an authorization request to the SRI web service for the given access key.
     *
     * Retries up to the configured number of attempts with configurable delays.
     *
     * @param string $accessKey A 49-digit SRI access key.
     * @return AuthorizationResult The result of the authorization attempt.
     * @throws InvalidAccessKeyException If the access key format is invalid.
     */
    public function authorize(string $accessKey): AuthorizationResult
    {
        $this->assertAccessKey($accessKey);

        $attempts = 0;

        try
        {
            $client = $this->soapClientFactory->make(
                $this->config->authorizationWsdl(),
                $this->config->normalizedSoapOptions(),
            );

            while ($attempts < $this->config->maxAttempts)
            {
                $attempts++;

                try
                {
                    $response = $client->autorizacionComprobante([
                        'claveAccesoComprobante' => $accessKey,
                    ]);

                    if (! is_object($response))
                    {
                        return AuthorizationResult::failure(
                            status: null,
                            error: 'Invalid response from WebService SRI',
                            attempts: $attempts,
                        );
                    }

                    $this->lastResponse = $response;

                    $status = $this->responseParser->authorizationStatus($response);
                    $messages = $this->responseParser->authorizationMessages($response);

                    if ($this->responseParser->isAuthorizationSuccessful($response))
                    {
                        return AuthorizationResult::success(
                            status: $status,
                            authorizedDocument: $this->responseParser->authorizedDocument($response),
                            messages: $messages,
                            attempts: $attempts,
                            rawResponse: $response,
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
                        rawResponse: $response,
                    );
                } catch (SoapFault $exception)
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
        } catch (SoapFault $exception)
        {
            return AuthorizationResult::failure(
                status: null,
                error: ConnectionException::soap($exception->getMessage())->getMessage(),
                attempts: $attempts,
            );
        }
    }

    /**
     * Returns the raw SOAP response from the last authorization request.
     *
     * @return object|null The raw response object, or null if no request has been made.
     */
    public function lastResponse(): ?object
    {
        return $this->lastResponse;
    }

    /**
     * Validates that the access key is a 49-digit string.
     *
     * @param string $accessKey The access key to validate.
     * @return void
     * @throws InvalidAccessKeyException If the access key does not match the expected format.
     */
    private function assertAccessKey(string $accessKey): void
    {
        if (! preg_match('/^\d{49}$/', $accessKey))
        {
            throw InvalidAccessKeyException::make($accessKey);
        }
    }

    /**
     * Converts an array of Message objects to a single human-readable string.
     *
     * @param array<int, Message> $messages The messages to convert.
     * @return string A concatenated string of all messages separated by newlines.
     */
    public function messagesToString(array $messages): string
    {
        if ($messages === [])
        {
            return 'WebService SRI not responding or return an empty response';
        }

        return implode(
            "\n",
            array_map(
                static fn ($message): string => $message->toString(),
                $messages,
            ),
        );
    }
}
