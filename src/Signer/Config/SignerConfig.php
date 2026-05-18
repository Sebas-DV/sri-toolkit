<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Config;

/**
 * Configuration container for the XML signer.
 *
 * Holds all configurable values used throughout the signing process,
 * including XML version, encoding, timezone, and XML namespace URIs.
 */
final readonly class SignerConfig
{
    /**
     * @param string $xmlVersion The XML declaration version.
     * @param string $encoding The document encoding.
     * @param string $timeZone The timezone for signing timestamps.
     * @param string $documentReferenceId The expected root element ID attribute value.
     * @param string $signatureNamespace The XMLDSig namespace URI.
     * @param string $xadesNamespace The XAdES namespace URI.
     */
    public function __construct(
        public string $xmlVersion = '1.0',
        public string $encoding = 'UTF-8',
        public string $timeZone = 'America/Guayaquil',
        public string $documentReferenceId = 'comprobante',
        public string $signatureNamespace = 'http://www.w3.org/2000/09/xmldsig#',
        public string $xadesNamespace = 'http://uri.etsi.org/01903/v1.3.2#',
    ) {
    }
}
