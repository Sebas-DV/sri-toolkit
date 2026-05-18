<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\XMLMaker\Builders\DeliveryGuideXmlBuilder;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
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
        return XmlGenerationData::make(
            documentType: XmlDocumentType::DeliveryGuide,
            environment: XmlEnvironment::Testing,
            accessKey: '1305202606179001234500110010010000000251234567818',
            data: [
                'sequential' => '000000025',
                'company' => [
                    'ruc' => '1790012345001',
                    'legal_name' => 'MTZ TEST S.A.',
                    'head_office_address' => 'Quito',
                    'special_taxpayer_number' => '123',
                ],
                'establishment' => [
                    'code' => '001',
                ],
                'emission_point' => [
                    'code' => '001',
                ],
                'carrier' => [
                    'name' => 'TRANSPORTISTA TEST',
                    'identification_type' => '04',
                    'identification_number' => '1790012345001',
                    'plate' => 'PBA1234',
                ],
                'shipping' => [
                    'start_address' => 'Quito',
                    'start_date' => '13/05/2026',
                    'end_date' => '14/05/2026',
                ],
                'establishment_address' => 'Quito',
                'rise' => 'RISE-001',
                'requires_accounting' => 'NO',
                'recipients' => [
                    [
                        'identification_number' => '1710034065',
                        'name' => 'CLIENTE TEST',
                        'destination_address' => 'Guayaquil',
                        'reason' => 'Venta',
                        'supporting_document_code' => '01',
                        'supporting_document_number' => '001-001-000000025',
                        'supporting_document_authorization' => '1305202601179001234500110010010000000251234567817',
                        'supporting_document_emission_date' => '13/05/2026',
                        'details' => [
                            [
                                'main_code' => 'P001',
                                'description' => 'Producto de prueba',
                                'quantity' => '1.000000',
                                'additional_info' => [
                                    'Lote' => 'A1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );
    }
}
