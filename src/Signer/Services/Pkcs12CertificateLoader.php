<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Services;

use MTZ\Toolkit\Signer\Contract\CertificateLoaderInterface;
use MTZ\Toolkit\Signer\Data\CertificateData;
use MTZ\Toolkit\Signer\Exceptions\CertificateException;

/**
 * Loads and parses PKCS#12 (.p12 / .pfx) certificate files.
 *
 * Extracts the private key, certificate, and associated metadata required
 * for XAdES-BES XML digital signatures.
 */
class Pkcs12CertificateLoader implements CertificateLoaderInterface
{
    /**
     * Load and parse a PKCS#12 certificate file.
     *
     * @param string $certificatePath The filesystem path to the PKCS#12 file.
     * @param string $certificatePassword The password protecting the certificate.
     * @return CertificateData The extracted certificate and private key data.
     * @throws CertificateException When the file is unreadable, the password is invalid,
     *                              or required key/cert data is missing.
     */
    public function load(string $certificatePath, string $certificatePassword): CertificateData
    {
        if (! is_file($certificatePath) || ! is_readable($certificatePath))
        {
            throw CertificateException::unreadable($certificatePath);
        }

        $pkcs12 = file_get_contents($certificatePath);

        if ($pkcs12 === false || $pkcs12 === '')
        {
            throw CertificateException::unreadable($certificatePath);
        }

        $certificates = [];

        if (! openssl_pkcs12_read($pkcs12, $certificates, $certificatePassword))
        {
            throw CertificateException::invalidPassword();
        }

        $privateKeyPem = $certificates['pkey'] ?? null;
        $certificatePem = $certificates['cert'] ?? null;

        if (! is_string($privateKeyPem) || $privateKeyPem === '')
        {
            throw CertificateException::privateKeyNotFound();
        }

        if (! is_string($certificatePem) || $certificatePem === '')
        {
            throw CertificateException::certificateNotFound();
        }

        $certificateContent = $this->extractCertificateContent($certificatePem);
        $certificateObject = $this->parseCertificate($certificatePem);
        $privateKeyData = $this->extractPrivateKeyData($privateKeyPem);

        return new CertificateData(
            privateKeyPem: $privateKeyPem,
            certificatePem: $certificatePem,
            certificateContent: $certificateContent,
            issuerName: $this->formatIssuer($certificateObject['issuer'] ?? []),
            serialNumber: (string) ($certificateObject['serialNumber'] ?? ''),
            modulus: $privateKeyData['modulus'],
            exponent: $privateKeyData['exponent'],
        );
    }

    /**
     * Extract the Base64 body from a PEM certificate.
     *
     * @param string $certificatePem The certificate in PEM format.
     * @return string The Base64-encoded DER certificate body (whitespace removed).
     * @throws CertificateException When the PEM headers are not found.
     */
    private function extractCertificateContent(string $certificatePem): string
    {
        if (! preg_match('/-----BEGIN CERTIFICATE-----\s*(.*?)\s*-----END CERTIFICATE-----/s', $certificatePem, $matches))
        {
            throw CertificateException::certificateNotFound();
        }

        return preg_replace('/\s+/', '', $matches[1]) ?? '';
    }

    /**
     * Parse a PEM certificate into an associative array.
     *
     * @param string $certificatePem The X.509 certificate in PEM format.
     * @return array Parsed certificate details from openssl_x509_parse().
     * @throws CertificateException When the certificate cannot be parsed.
     */
    private function parseCertificate(string $certificatePem): array
    {
        $details = openssl_x509_parse($certificatePem);

        if (! is_array($details))
        {
            throw CertificateException::cannotParseCertificate();
        }

        return $details;
    }

    /**
     * Extract RSA modulus and exponent from a PEM-encoded private key.
     *
     * @param string $privateKeyPem The private key in PEM format.
     * @return array{modulus: string, exponent: string} The RSA modulus and exponent as raw binary strings.
     * @throws CertificateException When the private key is invalid or missing RSA details.
     */
    private function extractPrivateKeyData(string $privateKeyPem): array
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if ($privateKey === false)
        {
            throw CertificateException::privateKeyNotFound();
        }

        $details = openssl_pkey_get_details($privateKey);

        if (! is_array($details) || ! isset($details['rsa']))
        {
            throw CertificateException::privateKeyNotFound();
        }

        return [
            'modulus' => $details['rsa']['n'],
            'exponent' => $details['rsa']['e'],
        ];
    }

    /**
     * Format the certificate issuer array into a standard DN string.
     *
     * @param array $issuer Associative array from openssl_x509_parse() issuer field.
     * @return string The formatted issuer distinguished name (e.g. "CN=..., O=...").
     */
    private function formatIssuer(array $issuer): string
    {
        $items = [];

        foreach (array_reverse($issuer) as $key => $value)
        {
            $key = $key === 'E' ? 'EMAILADDRESS' : (string)$key;
            $items[] = "{$key}={$value}";
        }

        return implode(', ', $items);
    }
}
