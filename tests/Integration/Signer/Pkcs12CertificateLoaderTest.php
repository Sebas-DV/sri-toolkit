<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Integration\Signer;

use MTZ\Toolkit\Signer\Exceptions\CertificateException;
use MTZ\Toolkit\Signer\Services\Pkcs12CertificateLoader;
use MTZ\Toolkit\Tests\Support\Signer\TemporaryCertificateFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class Pkcs12CertificateLoaderTest extends TestCase
{
    #[Test]
    public function it_loads_a_generated_pkcs12_certificate(): void
    {
        $certificate = TemporaryCertificateFactory::make();

        try
        {
            $data = (new Pkcs12CertificateLoader())->load(
                certificatePath: $certificate->path,
                certificatePassword: $certificate->password,
            );

            $this->assertStringContainsString('BEGIN PRIVATE KEY', $data->privateKeyPem);
            $this->assertStringContainsString('BEGIN CERTIFICATE', $data->certificatePem);

            $this->assertNotEmpty($data->certificateContent);
            $this->assertNotEmpty($data->issuerName);
            $this->assertNotEmpty($data->serialNumber);
            $this->assertNotEmpty($data->modulus);
            $this->assertNotEmpty($data->exponent);

            $this->assertStringContainsString('MTZ Testing Certificate', $data->issuerName);
        } finally
        {
            $certificate->cleanup();
        }
    }

    #[Test]
    public function it_fails_when_generated_certificate_password_is_wrong(): void
    {
        $certificate = TemporaryCertificateFactory::make();

        try
        {
            $this->expectException(CertificateException::class);

            (new Pkcs12CertificateLoader())->load(
                certificatePath: $certificate->path,
                certificatePassword: 'wrong-password',
            );
        } finally
        {
            $certificate->cleanup();
        }
    }

    #[Test]
    public function it_fails_when_certificate_file_does_not_exist(): void
    {
        $this->expectException(CertificateException::class);

        (new Pkcs12CertificateLoader())->load(
            certificatePath: __DIR__ . '/missing-certificate.p12',
            certificatePassword: 'secret',
        );
    }
}
