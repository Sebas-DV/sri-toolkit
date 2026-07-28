<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\Tests\Support\XMLMaker\SampleXmlPayloads;
use MTZ\Toolkit\XMLMaker\Builders\DeliveryGuideXmlBuilder;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DeliveryGuideXmlBuilderTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_builds_a_delivery_guide_xml(): void
    {
        $xml = (new DeliveryGuideXmlBuilder())->build($this->deliveryGuideData())->toString();

        $this->assertStringContainsString('<guiaRemision id="comprobante" version="1.1.0">', $xml);
        $this->assertStringContainsString('<codDoc>06</codDoc>', $xml);
        $this->assertStringContainsString('<infoGuiaRemision>', $xml);
        $this->assertStringContainsString('<rise>RISE-001</rise>', $xml);
        $this->assertStringContainsString('<contribuyenteEspecial>123</contribuyenteEspecial>', $xml);
        $this->assertStringContainsString('<destinatarios>', $xml);
        $this->assertStringContainsString('<detAdicional nombre="Lote" valor="A1"/>', $xml);

        $document = new DOMDocument();

        $this->assertTrue($document->loadXML($xml));
    }

    private function deliveryGuideData(): XmlGenerationData
    {
        return SampleXmlPayloads::deliveryGuide();
    }
}
