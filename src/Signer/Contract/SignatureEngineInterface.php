<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Contract;

use MTZ\Toolkit\Signer\Exceptions\CertificateException;

/**
 * Contract for cryptographic signing operations.
 */
interface SignatureEngineInterface
{
    /**
     * Create a SHA-1 based RSA signature over the given content.
     *
     * @param string $content The canonicalized content to sign.
     * @param string $privateKeyPem The private key in PEM format.
     * @return string The Base64-encoded signature.
     * @throws CertificateException When signing fails.
     */
    public function signSha1(string $content, string $privateKeyPem): string;
}
