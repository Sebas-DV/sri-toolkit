<?php

namespace MTZ\Toolkit\Signer\Support;

use MTZ\Toolkit\Signer\Exceptions\CertificateException;

final class OpenSslSignature
{
    public function signSha1(string $content, string $privateKeyPem): string
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if ($privateKey === false)
        {
            throw CertificateException::privateKeyNotFound();
        }

        $signature = '';

        if (! openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA1))
        {
            throw new CertificateException('Failed to create digital signature.');
        }

        return base64_decode($signature);
    }
}