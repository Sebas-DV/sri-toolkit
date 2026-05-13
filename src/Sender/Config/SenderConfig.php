<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Config;

use http\Env;
use MTZ\Toolkit\Sender\Enums\Environment;

final readonly class SenderConfig
{
    private const PRODUCTION_RECEPTION_URL = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
    private const TEST_RECEPTION_URL = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';

    private const PRODUCTION_AUTHORIZATION_URL = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';
    private const TEST_AUTHORIZATION_URL = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';

    public function __construct(
        public Environment $environment = Environment::Testing,
        public int $maxAttempts = 5,
        public int $retryDelay = 1,
        public int $sendDelay = 3,
        public array $soapOptions = [],
    )
    {
    }

    public function receptionWsdl(): string
    {
        return $this->environment === Environment::Testing
            ? self::TEST_RECEPTION_URL
            : self::PRODUCTION_RECEPTION_URL;
    }

    public function authorizationWsdl(): string
    {
        return $this->environment === Environment::Testing
            ? self::TEST_AUTHORIZATION_URL
            : self::PRODUCTION_AUTHORIZATION_URL;
    }

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