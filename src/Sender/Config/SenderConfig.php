<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Config;

use MTZ\Toolkit\Sender\Enums\Environment;

/**
 * Configuration container for the SRI sender module.
 *
 * Holds environment settings, WSDL URLs for both testing and production,
 * and tunables for retry attempts, delays, and SOAP client options.
 */
final readonly class SenderConfig
{
    /** @var string WSDL URL for the production reception endpoint. */
    private const PRODUCTION_RECEPTION_URL = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
    /** @var string WSDL URL for the testing reception endpoint. */
    private const TEST_RECEPTION_URL = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';

    /** @var string WSDL URL for the production authorization endpoint. */
    private const PRODUCTION_AUTHORIZATION_URL = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';
    /** @var string WSDL URL for the testing authorization endpoint. */
    private const TEST_AUTHORIZATION_URL = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';

    /** @var string WSDL URL for the production consultation endpoint. */
    private const PRODUCTION_CONSULTATION_URL = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/ConsultaComprobante?wsdl';
    /** @var string WSDL URL for the testing consultation endpoint. */
    private const TEST_CONSULTATION_URL = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/ConsultaComprobante?wsdl';

    /**
     * @param Environment $environment The target SRI environment (testing or production).
     * @param int $maxAttempts Maximum number of authorization retry attempts.
     * @param int $retryDelay Seconds to wait between authorization retries.
     * @param int $sendDelay Seconds to wait between reception and authorization steps.
     * @param array<string, mixed> $soapOptions Additional SOAP client options merged with defaults.
     */
    public function __construct(
        public Environment $environment = Environment::Testing,
        public int $maxAttempts = 5,
        public int $retryDelay = 1,
        public int $sendDelay = 3,
        public array $soapOptions = [],
    ) {
    }

    /**
     * Returns the WSDL URL for the reception web service based on the current environment.
     *
     * @return string The reception WSDL URL.
     */
    public function receptionWsdl(): string
    {
        return $this->environment === Environment::Testing
            ? self::TEST_RECEPTION_URL
            : self::PRODUCTION_RECEPTION_URL;
    }

    /**
     * Returns the WSDL URL for the authorization web service based on the current environment.
     *
     * @return string The authorization WSDL URL.
     */
    public function authorizationWsdl(): string
    {
        return $this->environment === Environment::Testing
            ? self::TEST_AUTHORIZATION_URL
            : self::PRODUCTION_AUTHORIZATION_URL;
    }

    /**
     * Returns the WSDL URL for the consultation web service based on the current environment.
     *
     * @return string The consultation WSDL URL.
     */
    public function consultationWsdl(): string
    {
        return $this->environment === Environment::Testing
            ? self::TEST_CONSULTATION_URL
            : self::PRODUCTION_CONSULTATION_URL;
    }

    /**
     * Merges default SOAP options with user-provided options.
     *
     * @return array<string, mixed> The normalized SOAP client options.
     */
    public function normalizedSoapOptions(): array
    {
        return array_merge([
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'user_agent' => 'MTZ/Toolkit',
            'connection_timeout' => 180,
            'exceptions' => true,
        ], $this->soapOptions);
    }
}
