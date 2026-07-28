<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use DOMXPath;
use MTZ\Toolkit\Tests\Support\XMLMaker\SampleXmlPayloads;
use MTZ\Toolkit\XMLMaker\Builders\InvoiceXmlBuilder;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceXmlBuilderTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_builds_an_invoice_xml(): void
    {
        $result = (new InvoiceXmlBuilder())->build($this->invoiceData());

        $xml = $result->toString();

        $this->assertStringContainsString('<factura id="comprobante" version="2.1.0">', $xml);
        $this->assertStringContainsString('<ambiente>1</ambiente>', $xml);
        $this->assertStringContainsString('<codDoc>01</codDoc>', $xml);
        $this->assertStringContainsString('<claveAcceso>1305202601179001234500110010010000000251234567817</claveAcceso>', $xml);
        $this->assertStringContainsString('<contribuyenteEspecial>123</contribuyenteEspecial>', $xml);
        $this->assertStringContainsString('<guiaRemision>001-001-000000001</guiaRemision>', $xml);
        $this->assertStringContainsString('<razonSocialComprador>CONSUMIDOR FINAL</razonSocialComprador>', $xml);
        $this->assertStringContainsString('<totalSubsidio>0.00</totalSubsidio>', $xml);
        $this->assertStringContainsString('<codDocReembolso>41</codDocReembolso>', $xml);
        $this->assertStringContainsString('<valorDevolucionIva>0.00</valorDevolucionIva>', $xml);
        $this->assertStringContainsString('<unidadMedida>UND</unidadMedida>', $xml);
        $this->assertStringContainsString('<precioSinSubsidio>12.00</precioSinSubsidio>', $xml);
        $this->assertStringContainsString('<detAdicional nombre="Color" valor="Azul"/>', $xml);
        $this->assertStringContainsString('<valorRetIva>0.00</valorRetIva>', $xml);
        $this->assertStringContainsString('<valorRetRenta>0.00</valorRetRenta>', $xml);
        $this->assertStringContainsString('<campoAdicional nombre="Email">cliente@example.com</campoAdicional>', $xml);

        $document = new DOMDocument();

        $this->assertTrue($document->loadXML($xml));

        $xpath = new DOMXPath($document);

        $this->assertSame('factura', $document->documentElement?->nodeName);
        $this->assertSame('1', $xpath->evaluate('string(/factura/infoTributaria/ambiente)'));
        $this->assertSame('000000025', $xpath->evaluate('string(/factura/infoTributaria/secuencial)'));
    }

    /**
     * @throws DOMException
     */
    #[Test]
    public function it_fails_when_details_are_empty(): void
    {
        $data = $this->invoiceData()->data;
        $data['details'] = [];

        $this->expectException(InvalidXmlDataException::class);

        (new InvoiceXmlBuilder())->build(
            XmlGenerationData::make(
                documentType: XmlDocumentType::Invoice,
                environment: XmlEnvironment::Testing,
                accessKey: '1305202601179001234500110010010000000251234567817',
                data: $data,
            ),
        );
    }

    private function invoiceData(): XmlGenerationData
    {
        return SampleXmlPayloads::invoice();
    }
}
