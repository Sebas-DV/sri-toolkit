<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Clients;

use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Contracts\SoapClientFactoryInterface;
use MTZ\Toolkit\Sender\Data\AuthorizationResult;
use MTZ\Toolkit\Sender\Data\BatchAuthorizationResult;
use MTZ\Toolkit\Sender\Exceptions\ConnectionException;
use MTZ\Toolkit\Sender\Exceptions\InvalidAccessKeyException;
use MTZ\Toolkit\Sender\Services\ResponseParser;
use MTZ\Toolkit\Sender\Support\NativeSoapClientFactory;
use SoapFault;

/**
 * Authorizes a batch (lote) of vouchers via the SRI authorization web service
 * using the batch access key (autorizacionComprobanteLote).
 */
final readonly class BatchClient
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
     * Authorizes a batch by its batch access key.
     *
     * @param string $loteAccessKey The 49-digit batch access key.
     * @return BatchAuthorizationResult The per-voucher authorization results.
     * @throws InvalidAccessKeyException If the access key format is invalid.
     */
    public function authorize(string $loteAccessKey): BatchAuthorizationResult
    {
        $this->assertAccessKey($loteAccessKey);

        try
        {
            $client = $this->soapClientFactory->make(
                $this->config->authorizationWsdl(),
                $this->config->normalizedSoapOptions(),
            );

            $response = $client->__soapCall('autorizacionComprobanteLote', [
                ['claveAccesoLote' => $loteAccessKey],
            ]);

            if (! is_object($response))
            {
                return BatchAuthorizationResult::failure('Invalid response from WebService SRI', $loteAccessKey);
            }

            $authorizations = $this->responseParser->batchAuthorizations($response);

            if ($authorizations === [])
            {
                return BatchAuthorizationResult::failure(
                    'The SRI batch authorization returned no vouchers.',
                    $loteAccessKey,
                    rawResponse: $response,
                );
            }

            $allAuthorized = $this->allAuthorized($authorizations);

            if (! $allAuthorized)
            {
                return BatchAuthorizationResult::failure(
                    'One or more vouchers in the batch were not authorized.',
                    $loteAccessKey,
                    $authorizations,
                    $response,
                );
            }

            return BatchAuthorizationResult::success($loteAccessKey, $authorizations, $response);
        } catch (SoapFault $exception)
        {
            return BatchAuthorizationResult::failure(
                ConnectionException::soap($exception->getMessage())->getMessage(),
                $loteAccessKey,
            );
        }
    }

    /**
     * @param list<AuthorizationResult> $authorizations
     */
    private function allAuthorized(array $authorizations): bool
    {
        foreach ($authorizations as $result)
        {
            if (! $result->success)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates that the batch access key is a 49-digit string.
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
