<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use DOMXPath;
use MTZ\Toolkit\Tests\Support\XMLMaker\SampleXmlPayloads;
use MTZ\Toolkit\XMLMaker\Builders\PurchaseSettlementXmlBuilder;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
use MTZ\Toolkit\XMLMaker\Exceptions\InvalidXmlDataException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PurchaseSettlementXmlBuilderTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_builds_a_purchase_settlement_xml(): void
    {
        $result = (new PurchaseSettlementXmlBuilder())->build($this->purchaseSettlementData());

        $xml = $result->toString();

        $this->assertStringContainsString('<liquidacionCompra id="comprobante" version="1.1.0">', $xml);
        $this->assertStringContainsString('<codDoc>03</codDoc>', $xml);
        $this->assertStringContainsString('<claveAcceso>1305202603179001234500110010010000000301234567811</claveAcceso>', $xml);
        $this->assertStringContainsString('<tipoIdentificacionProveedor>05</tipoIdentificacionProveedor>', $xml);
        $this->assertStringContainsString('<razonSocialProveedor>PROVIDER TEST</razonSocialProveedor>', $xml);
        $this->assertStringContainsString('<direccionProveedor>Cuenca</direccionProveedor>', $xml);
        $this->assertStringContainsString('<formaPago>01</formaPago>', $xml);
        $this->assertStringContainsString('<detAdicional nombre="Batch" valor="A1"/>', $xml);
        $this->assertStringContainsString('<campoAdicional nombre="Email">provider@example.com</campoAdicional>', $xml);

        $document = new DOMDocument();

        $this->assertTrue($document->loadXML($xml));

        $xpath = new DOMXPath($document);

        $this->assertSame('liquidacionCompra', $document->documentElement?->nodeName);
        $this->assertSame('03', $xpath->evaluate('string(/liquidacionCompra/infoTributaria/codDoc)'));
        $this->assertSame('000000030', $xpath->evaluate('string(/liquidacionCompra/infoTributaria/secuencial)'));
        $this->assertSame('11.50', $xpath->evaluate('string(/liquidacionCompra/infoLiquidacionCompra/importeTotal)'));
    }

    /**
     * @throws DOMException
     */
    #[Test]
    public function it_fails_when_details_are_empty(): void
    {
        $data = $this->purchaseSettlementData()->data;
        $data['details'] = [];

        $this->expectException(InvalidXmlDataException::class);

        (new PurchaseSettlementXmlBuilder())->build(
            XmlGenerationData::make(
                documentType: XmlDocumentType::PurchaseSettlement,
                environment: XmlEnvironment::Testing,
                accessKey: '1305202603179001234500110010010000000301234567811',
                data: $data,
            ),
        );
    }

    private function purchaseSettlementData(): XmlGenerationData
    {
        return SampleXmlPayloads::purchaseSettlement();
    }
}
