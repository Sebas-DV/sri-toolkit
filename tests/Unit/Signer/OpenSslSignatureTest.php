<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Signer;

use MTZ\Toolkit\Signer\Exceptions\CertificateException;
use MTZ\Toolkit\Signer\Services\Pkcs12CertificateLoader;
use MTZ\Toolkit\Signer\Support\OpenSslSignature;
use MTZ\Toolkit\Tests\Support\Signer\TemporaryCertificateFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OpenSslSignatureTest extends TestCase
{
    #[Test]
    public function it_signs_content_using_sha1_and_private_key(): void
    {
        $certificate = TemporaryCertificateFactory::make();

        try
        {
            $certificateData = (new Pkcs12CertificateLoader())->load(
                certificatePath: $certificate->path,
                certificatePassword: $certificate->password,
            );

            $content = 'content to sign';

            $signature = (new OpenSslSignature())->signSha1(
                content: $content,
                privateKeyPem: $certificateData->privateKeyPem,
            );

            $this->assertNotEmpty($signature);

            $decodedSignature = base64_decode($signature, true);

            if ($decodedSignature === false)
            {
                $this->fail('Failed to decode signature');
            }

            $verification = openssl_verify(
                $content,
                $decodedSignature,
                $certificateData->certificatePem,
                OPENSSL_ALGO_SHA1,
            );

            $this->assertSame(1, $verification);
        } finally
        {
            $certificate->cleanup();
        }
    }

    #[Test]
    public function it_fails_when_private_key_is_invalid(): void
    {
        $this->expectException(CertificateException::class);

        (new OpenSslSignature())->signSha1(
            content: 'Content to sign',
            privateKeyPem: 'INVALID_PRIVATE_KEY',
        );
    }
}
