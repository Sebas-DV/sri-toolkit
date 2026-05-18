<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Integration\Signer;

use DOMDocument;
use DOMException;
use DOMXPath;
use MTZ\Toolkit\Signer\Signer;
use MTZ\Toolkit\Tests\Support\Signer\FakeClock;
use MTZ\Toolkit\Tests\Support\Signer\FakeIdGenerator;
use MTZ\Toolkit\Tests\Support\Signer\TemporaryCertificateFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SignerWithGeneratedCertificateTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_signs_an_xml_using_a_generated_pkcs12_certificate(): void
    {
        $certificate = TemporaryCertificateFactory::make();

        try
        {
            $signer = new Signer(
                certificatePath: $certificate->path,
                certificatePassword: $certificate->password,
                clock: new FakeClock(),
                idGenerator: new FakeIdGenerator(),
            );

            $result = $signer
                ->loadXml($this->validInvoiceXml())
                ->signAsResult();

            $this->assertNotEmpty($result->xml);
            $this->assertSame('fake-id-3', $result->signatureId);
            $this->assertSame('2026-05-13T10:30:00-05:00', $result->signedAt);

            $this->assertStringContainsString('<ds:Signature', $result->xml);
            $this->assertStringContainsString('<ds:SignedInfo', $result->xml);
            $this->assertStringContainsString('<ds:SignatureValue', $result->xml);
            $this->assertStringContainsString('<ds:X509Certificate', $result->xml);
            $this->assertStringContainsString('<xades:SignedProperties', $result->xml);
            $this->assertStringContainsString('<xades:SigningTime>2026-05-13T10:30:00-05:00</xades:SigningTime>', $result->xml);

            $document = new DOMDocument();

            $this->assertTrue($document->loadXML($result->xml));
            $this->assertNotEmpty($this->signatureValue($result->xml));
        } finally
        {
            $certificate->cleanup();
        }
    }

    /**
     * @throws DOMException
     */
    #[Test]
    public function sign_returns_signed_xml_string_using_generated_certificate(): void
    {
        $certificate = TemporaryCertificateFactory::make();

        try
        {
            $signer = new Signer(
                certificatePath: $certificate->path,
                certificatePassword: $certificate->password,
                clock: new FakeClock(),
                idGenerator: new FakeIdGenerator(),
            );

            $signedXml = $signer
                ->loadXml($this->validInvoiceXml())
                ->sign();

            $this->assertStringContainsString('<ds:Signature', $signedXml);
            $this->assertStringContainsString('<xades:QualifyingProperties', $signedXml);
            $this->assertStringContainsString('<ds:X509Certificate', $signedXml);

            $document = new DOMDocument();

            $this->assertTrue($document->loadXML($signedXml));
        } finally
        {
            $certificate->cleanup();
        }
    }

    private function validInvoiceXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<factura id="comprobante" version="2.1.0">
    <infoTributaria>
        <ambiente>1</ambiente>
        <tipoEmision>1</tipoEmision>
        <razonSocial>MTZ TEST</razonSocial>
        <nombreComercial>MTZ TEST</nombreComercial>
        <ruc>1790012345001</ruc>
        <claveAcceso>1305202601179001234500110010010000000251234567817</claveAcceso>
        <codDoc>01</codDoc>
        <estab>001</estab>
        <ptoEmi>001</ptoEmi>
        <secuencial>000000025</secuencial>
        <dirMatriz>Quito</dirMatriz>
    </infoTributaria>
</factura>
XML;
    }

    private function signatureValue(string $signedXml): string
    {
        $document = new DOMDocument();
        $document->loadXML($signedXml);

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
