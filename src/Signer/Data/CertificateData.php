<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Data;

final readonly class CertificateData
{
    public function __construct(
        public string $privateKeyPem,
        public string $certificatePem,
        public string $certificateContent,
        public string $issuerName,
        public string $serialNumber,
        public string $modulus,
        public string $exponent,
    ) {}
}