<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Config;

final readonly class SignerConfig
{
    public function __construct(
        public string $xmlVersion = '1.0',
        public string $encoding = 'UTF-8',
        public string $timeZone = 'America/Guayaquil',
        public string $documentReferenceId = 'comprobante',
        public string $signatureNamespace = 'https://www.w3.org/2000/09/xmldsig#',
        public string $xadesNamespace = 'https://uri.etsi.org/01903/v1.3.2#',
    )
    {
    }
}