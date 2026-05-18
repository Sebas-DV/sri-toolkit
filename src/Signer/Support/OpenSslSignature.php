<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Support;

use MTZ\Toolkit\Signer\Contract\SignatureEngineInterface;
use MTZ\Toolkit\Signer\Exceptions\CertificateException;

final class OpenSslSignature implements SignatureEngineInterface
{
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
