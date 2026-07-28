<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\Tests\Support\XMLMaker\SampleXmlPayloads;
use MTZ\Toolkit\XMLMaker\Builders\WithholdingReceiptXmlBuilder;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WithholdingReceiptXmlBuilderTest extends TestCase
{
    /**
     * @throws DOMException
     */
    #[Test]
    public function it_builds_a_withholding_receipt_xml(): void
    {
        $xml = (new WithholdingReceiptXmlBuilder())->build($this->withholdingReceiptData())->toString();

        $this->assertStringContainsString('<comprobanteRetencion id="comprobante" version="2.0.0">', $xml);
        $this->assertStringContainsString('<codDoc>07</codDoc>', $xml);
        $this->assertStringContainsString('<infoCompRetencion>', $xml);
        $this->assertStringContainsString('<contribuyenteEspecial>123</contribuyenteEspecial>', $xml);
        $this->assertStringContainsString('<parteRel>NO</parteRel>', $xml);
        $this->assertStringContainsString('<docsSustento>', $xml);
        $this->assertStringContainsString('<impuestosDocSustento>', $xml);
        $this->assertStringContainsString('<retenciones>', $xml);
        $this->assertStringContainsString('<dividendos>', $xml);
        $this->assertStringContainsString('<compraCajBanano>', $xml);
        $this->assertStringContainsString('<reembolsos>', $xml);
        $this->assertStringContainsString('<pagos>', $xml);

        $document = new DOMDocument();

        $this->assertTrue($document->loadXML($xml));
    }

    private function withholdingReceiptData(): XmlGenerationData
    {
        return SampleXmlPayloads::withholdingReceipt();
    }
}
