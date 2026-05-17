<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support\Signer;

use MTZ\Toolkit\Signer\Contract\CertificateLoaderInterface;
use MTZ\Toolkit\Signer\Contract\IdGeneratorInterface;
use MTZ\Toolkit\Signer\Data\CertificateData;

final class FakeCertificateLoader implements CertificateLoaderInterface
{
    public function load(string $certificatePath, string $certificatePassword): CertificateData
    {
        return new CertificateData(
            privateKeyPem: 'FAKE_PRIVATE_KEY',
            certificatePem: 'FAKE_CERTIFICATE_PEM',
            certificateContent: base64_encode('FAKE_CERTIFICATE_CONTENT'),
            issuerName: 'CN=Fake Issuer,O=MTZ',
            serialNumber: '123456789',
            modulus: 'fake-modulus',
            exponent: 'fake-exponent',
        );
    }
}