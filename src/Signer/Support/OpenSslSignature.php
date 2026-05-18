<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Support;

use MTZ\Toolkit\Signer\Contract\SignatureEngineInterface;
use MTZ\Toolkit\Signer\Exceptions\CertificateException;

/**
 * OpenSSL-based implementation of the signature engine.
 *
 * Performs SHA-1 hashing and RSA signing via PHP's openssl extension.
 */
final class OpenSslSignature implements SignatureEngineInterface
{
    /**
     * Sign content using SHA-1 RSA with the given private key.
     *
     * @param string $content The canonicalized XML content to sign.
     * @param string $privateKeyPem The private key in PEM format.
     * @return string The Base64-encoded signature.
     * @throws CertificateException When the private key is invalid or signing fails.
     */
    public function signSha1(string $content, string $privateKeyPem): string
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if ($privateKey === false)
        {
            throw CertificateException::privateKeyNotFound();
        }

        $signature = '';

        $signed = openssl_sign(
            $content,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA1,
        );

        if (! $signed || $signature === '')
        {
            throw new CertificateException('Failed to create signature');
        }

        return base64_encode((string) $signature);
    }
}
