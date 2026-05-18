<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Integration\Signer;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\Signer\Config\SignerConfig;
use MTZ\Toolkit\Signer\Services\Pkcs12CertificateLoader;
use MTZ\Toolkit\Signer\Services\XadesBesXmlSigner;
use MTZ\Toolkit\Signer\Support\OpenSslSignature;
use MTZ\Toolkit\Tests\Support\Signer\FakeClock;
use MTZ\Toolkit\Tests\Support\Signer\FakeIdGenerator;
use MTZ\Toolkit\Tests\Support\Signer\TemporaryCertificateFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XadesBesXmlSignerWithGeneratedCertificateTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_builds_a_xades_signature_with_a_generated_certificate(): void
    {
        $certificate = TemporaryCertificateFactory::make();

        try
        {
            $certificateData = (new Pkcs12CertificateLoader())->load(
                certificatePath: $certificate->path,
                certificatePassword: $certificate->password,
            );

            $document = new DOMDocument('1.0', 'utf-8');
            $document->preserveWhiteSpace = false;
            $document->formatOutput = false;
            $document->loadXML('<factura id="comprobante"><infoTributaria/></factura>');

            $signer = new XadesBesXmlSigner(
                config: new SignerConfig(),
                clock: new FakeClock(),
                idGenerator: new FakeIdGenerator(),
                openSslSignature: new OpenSslSignature(),
            );

            $result = $signer->sign(document: $document, certificateData: $certificateData);

            $this->assertStringContainsString('Signature-fake-id-3', $result->xml);
            $this->assertStringContainsString('SignedProperties-fake-id-4', $result->xml);
            $this->assertStringContainsString('SignedInfo-fake-id-5', $result->xml);
            $this->assertStringContainsString('SignatureValue-fake-id-8', $result->xml);

            $this->assertStringContainsString('<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"', $result->xml);
            $this->assertStringContainsString('<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"', $result->xml);
            $this->assertStringContainsString('<xades:SigningCertificate>', $result->xml);
            $this->assertStringContainsString('<xades:DataObjectFormat ObjectReference="#DocumentRef-fake-id-7">', $result->xml);

            $parsed = new DOMDocument();

            $this->assertTrue($parsed->loadXML($result->xml));
        } finally
        {
            $certificate->cleanup();
        }
    }
}
