<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\Signer;

use DOMDocument;
use DOMException;
use DOMXPath;
use MTZ\Toolkit\Signer\Config\SignerConfig;
use MTZ\Toolkit\Signer\Data\CertificateData;
use MTZ\Toolkit\Signer\Services\XadesBesXmlSigner;
use MTZ\Toolkit\Tests\Support\Signer\FakeClock;
use MTZ\Toolkit\Tests\Support\Signer\FakeIdGenerator;
use MTZ\Toolkit\Tests\Support\Signer\FakeSignatureEngine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class XadesBesXmlSignerTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_builds_a_xades_bes_signature_structure_without_real_certificate(): void
    {
        $document = new DOMDocument('1.0', 'utf-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $document->loadXML('<factura id="comprobante"><infoTributaria/></factura>');

        $signatureEngine = new FakeSignatureEngine();

        $signer = new XadesBesXmlSigner(
            config: new SignerConfig(),
            clock: new FakeClock(),
            idGenerator: new FakeIdGenerator(),
            openSslSignature: $signatureEngine,
        );

        $result = $signer->sign(
            document: $document,
            certificateData: $this->fakeCertificateData(),
        );

        $this->assertStringContainsString('<ds:Signature', $result->xml);
        $this->assertStringContainsString('Signature-fake-id-3', $result->xml);
        $this->assertStringContainsString('SignedProperties-fake-id-4', $result->xml);
        $this->assertStringContainsString('SignedInfo-fake-id-5', $result->xml);
        $this->assertStringContainsString('SignatureValue-fake-id-8', $result->xml);
        $this->assertStringContainsString('<xades:SignedProperties', $result->xml);
        $this->assertStringContainsString('<xades:SigningTime>2026-05-13T10:30:00-05:00</xades:SigningTime>', $result->xml);

        $this->assertSame('fake-id-3', $result->signatureId);
        $this->assertSame('2026-05-13T10:30:00-05:00', $result->signedAt);

        $signatureValue = $this->signatureValue($result->xml);

        $this->assertSame('FAKE_SIGNATURE_VALUE', base64_decode($signatureValue, true));

        $this->assertCount(1, $signatureEngine->signedContents);
        $this->assertSame('FAKE_PRIVATE_KEY', $signatureEngine->signedContents[0]['private_key']);
    }

    /**
     * @throws DOMException
     */
    #[Test]
    public function it_fails_when_document_is_null(): void
    {
        $signer = new XadesBesXmlSigner(
            config: new SignerConfig(),
            clock: new FakeClock(),
            idGenerator: new FakeIdGenerator(),
            openSslSignature: new FakeSignatureEngine(),
        );

        $this->expectException(RuntimeException::class);

        $signer->sign(
            document: null,
            certificateData: $this->fakeCertificateData(),
        );
    }

    private function fakeCertificateData(): CertificateData
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

    private function signatureValue(string $xml): string
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);

        $nodes = $xpath->query('//*[local-name()="SignatureValue"]');

        if ($nodes === false)
        {
            return '';
        }

        $node = $nodes->item(0);

        return (string)$node?->nodeValue;
    }
}
