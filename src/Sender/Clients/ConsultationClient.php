<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Clients;

use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use MTZ\Toolkit\Sender\Data\ConsultationResult;
use MTZ\Toolkit\Sender\Exceptions\ConnectionException;
use MTZ\Toolkit\Sender\Exceptions\InvalidAccessKeyException;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Sender\Support\NativeSoapClientFactory;
use SoapFault;

/**
 * Queries a document's current authorization state via the SRI consultation
 * web service (ConsultaComprobante), independent of the send pipeline.
 */
final readonly class ConsultationClient
{
    /**
     * @param SenderConfig $config The sender configuration for WSDL URLs.
     * @param ResponseParser $responseParser Service that parses SOAP responses from the SRI.
     * @param SoapClientFactoryInterface $soapClientFactory Factory for creating SOAP clients.
     */
    public function __construct(
        private SenderConfig $config,
        private ResponseParser $responseParser = new ResponseParser(),
        private SoapClientFactoryInterface $soapClientFactory = new NativeSoapClientFactory(),
    ) {
    }

    /**
     * Queries the current state of a document by its access key.
     *
     * @param string $accessKey A 49-digit SRI access key.
     * @return ConsultationResult The current state reported by the SRI.
     * @throws InvalidAccessKeyException If the access key format is invalid.
     */
    public function query(string $accessKey): ConsultationResult
    {
        $this->assertAccessKey($accessKey);

        try
        {
            $client = $this->soapClientFactory->make(
                $this->config->consultationWsdl(),
                $this->config->normalizedSoapOptions(),
            );

            $response = $client->__soapCall('consultarEstadoAutorizacionComprobante', [
                ['claveAcceso' => $accessKey],
            ]);

            if (! is_object($response))
            {
                return ConsultationResult::failure('Invalid response from WebService SRI');
            }

            return $this->responseParser->consultationResult($response);
        } catch (SoapFault $exception)
        {
            return ConsultationResult::failure(
                ConnectionException::soap($exception->getMessage())->getMessage(),
            );
        }
    }

    /**
     * Validates that the access key is a 49-digit string.
     *
     * @param string $accessKey The access key to validate.
     * @throws InvalidAccessKeyException If the access key does not match the expected format.
     */
    private function assertAccessKey(string $accessKey): void
    {
        if (in_array(preg_match('/^\d{49}$/', $accessKey), [0, false], true))
        {
            throw InvalidAccessKeyException::make($accessKey);
        }
    }
}
