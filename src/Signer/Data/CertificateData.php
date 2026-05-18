<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Data;

/**
 * Value object holding parsed certificate and private key data.
 *
 * Contains all the fields required by the XAdES-BES signing process.
 */
final readonly class CertificateData
{
    /**
     * @param string $privateKeyPem The private key in PEM format.
     * @param string $certificatePem The X.509 certificate in PEM format.
     * @param string $certificateContent The Base64-encoded DER certificate body (without headers).
     * @param string $issuerName The formatted issuer distinguished name.
     * @param string $serialNumber The certificate serial number.
     * @param string $modulus The RSA modulus (raw binary string).
     * @param string $exponent The RSA exponent (raw binary string).
     */
    public function __construct(
        public string $privateKeyPem,
        public string $certificatePem,
        public string $certificateContent,
        public string $issuerName,
        public string $serialNumber,
        public string $modulus,
        public string $exponent,
    ) {
    }
}
