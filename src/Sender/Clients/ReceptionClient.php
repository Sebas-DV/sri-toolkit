<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Clients;

use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use MTZ\Toolkit\Sender\Data\Message;
use MTZ\Toolkit\Sender\Data\ReceptionResult;
use MTZ\Toolkit\Sender\Exceptions\ConnectionException;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Sender\Support\NativeSoapClientFactory;
use SoapFault;

/**
 * Sends signed XML documents to the SRI reception web service for validation.
 *
 * This client submits the signed XML to the SRI reception endpoint, parses the SOAP
 * response, and returns a reception result indicating whether the document was received
 * or returned with errors.
 */
final class ReceptionClient
{
    /**
     * @var object|null The raw SOAP response from the last reception request.
     */
    private ?object $lastResponse = null;

    /**
     * @param SenderConfig $config The sender configuration for WSDL URLs.
     * @param ResponseParser $responseParser Service that parses SOAP responses from the SRI.
     * @param SoapClientFactoryInterface $soapClientFactory Factory for creating SOAP clients.
     */
    public function __construct(
        private readonly SenderConfig $config,
        private readonly ResponseParser $responseParser = new ResponseParser(),
        private readonly SoapClientFactoryInterface $soapClientFactory = new NativeSoapClientFactory(),
    ) {
    }

    /**
     * Submits a signed XML document to the SRI reception web service for validation.
     *
     * @param string $signedXml The signed XML document to validate.
     * @return ReceptionResult The result of the reception attempt.
     */
    public function validate(string $signedXml): ReceptionResult
    {
        try
        {
            $client = $this->soapClientFactory->make(
                $this->config->receptionWsdl(),
                $this->config->normalizedSoapOptions(),
            );

            $response = $client->validarComprobante([
                'xml' => $signedXml,
            ]);

            if (! is_object($response))
            {
                return ReceptionResult::failure(
                    status: null,
                    error: 'Invalid response from WebService SRI',
                );
            }

            $this->lastResponse = $response;

            $status = $this->responseParser->receptionStatus($response);
            $messages = $this->responseParser->receptionMessage($response);

            if (! $this->responseParser->isReceptionSuccessful($response))
            {
                return ReceptionResult::failure(
                    status: $status,
                    error: $this->messagesToString($messages),
                    messages: $messages,
                    rawResponse: $response,
                );
            }

            return ReceptionResult::success(
                status: $status,
                messages: $messages,
                rawResponse: $response,
            );
        } catch (SoapFault $exception)
        {
            return ReceptionResult::failure(
                status: null,
                error: ConnectionException::soap($exception->getMessage())->getMessage(),
            );
        }
    }

    /**
     * Returns the raw SOAP response from the last reception request.
     *
     * @return object|null The raw response object, or null if no request has been made.
     */
    public function lastResponse(): ?object
    {
        return $this->lastResponse;
    }

    /**
     * Converts an array of Message objects to a single human-readable string.
     *
     * @param array<int, Message> $messages The messages to convert.
     * @return string A concatenated string of all messages separated by newlines.
     */
    private function messagesToString(array $messages): string
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
