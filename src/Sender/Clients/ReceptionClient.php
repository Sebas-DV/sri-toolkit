<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Clients;

use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use MTZ\Toolkit\Sender\Data\ReceptionResult;
use MTZ\Toolkit\Sender\Exceptions\ConnectionException;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Sender\Support\NativeSoapClientFactory;
use SoapFault;

final class ReceptionClient
{
    private ?object $lastResponse = null;

    public function __construct(
        private readonly SenderConfig $config,
        private readonly ResponseParser $responseParser = new ResponseParser(),
        private readonly SoapClientFactoryInterface $soapClientFactory = new NativeSoapClientFactory(),
    )
    {
    }

    public function validate(string $signedXml): ReceptionResult
    {
        try
        {
            $client = $this->soapClientFactory->make(
                $this->config->receptionWsdl(),
                $this->config->normalizedSoapOptions()
            );

            $this->lastResponse = $client->validarComprobante([
                'xml' => $signedXml,
            ]);

            $status = $this->responseParser->receptionStatus($this->lastResponse);
            $messages = $this->responseParser->receptionMessage($this->lastResponse);

            if (! $this->responseParser->isReceptionSuccessful($this->lastResponse))
            {
                return ReceptionResult::failure(
                    status: $status,
                    error: $this->messagesToString($messages),
                    messages: $messages,
                    rawResponse: $this->lastResponse,
                );
            }

            return ReceptionResult::success(
                status: $status,
                messages: $messages,
                rawResponse: $this->lastResponse,
            );
        }
        catch (SoapFault $exception)
        {
            return ReceptionResult::failure(
                status: null,
                error: ConnectionException::soap($exception->getMessage())->getMessage(),
            );
        }
    }

    public function lastResponse(): ?object
    {
        return $this->lastResponse;
    }

    private function messagesToString(array $messages): string
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