<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Services;

use MTZ\Toolkit\Signer\Contract\CertificateLoaderInterface;
use MTZ\Toolkit\Signer\Data\CertificateData;
use MTZ\Toolkit\Signer\Exceptions\CertificateException;

class Pkcs12CertificateLoader implements CertificateLoaderInterface
{
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

    private function extractCertificateContent(string $certificatePem): string
    {
        if (! preg_match('/-----BEGIN CERTIFICATE-----\s*(.*?)\s*-----END CERTIFICATE-----/s', $certificatePem, $matches))
        {
            throw CertificateException::certificateNotFound();
        }

        return preg_replace('/\s+/', '', $matches[1]) ?? '';
    }

    private function parseCertificate(string $certificatePem): array
    {
        $details = openssl_x509_parse($certificatePem);

        if (! is_array($details))
        {
            throw CertificateException::cannotParseCertificate();
        }

        return $details;
    }

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

    private function formatIssuer(array $issuer): string
    {
        $items = [];

        foreach (array_reverse($issuer) as $key => $value) {
            $key = $key === 'E' ? 'EMAILADDRESS' : (string)$key;
            $items[] = "{$key}={$value}";
        }

        return implode(', ', $items);
    }
}