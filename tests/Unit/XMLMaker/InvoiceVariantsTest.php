<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use MTZ\Toolkit\Tests\Support\XMLMaker\SampleXmlPayloads;
use MTZ\Toolkit\XMLMaker\Builders\InvoiceXmlBuilder;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceVariantsTest extends TestCase
{
    #[Test]
    public function it_builds_a_schema_valid_invoice_with_all_variants(): void
    {
        $xml = (new InvoiceXmlBuilder())->build($this->variantData())->toString();

        $errors = (new XsdValidator())->validate($xml, XmlDocumentType::Invoice);

        $this->assertSame([], $errors, implode("\n", $errors));

        $this->assertStringContainsString('<comercioExterior>EXPORTADOR</comercioExterior>', $xml);
        $this->assertStringContainsString('<incoTermFactura>FOB</incoTermFactura>', $xml);
        $this->assertStringContainsString('<fleteInternacional>0.00</fleteInternacional>', $xml);
        $this->assertStringContainsString('<reembolsoDetalle>', $xml);
        $this->assertStringContainsString('<infoSustitutivaGuiaRemision>', $xml);
        $this->assertStringContainsString('<otrosRubrosTerceros>', $xml);
        $this->assertStringContainsString('<maquinaFiscal>', $xml);
    }

    private function variantData(): XmlGenerationData
    {
        $data = SampleXmlPayloads::invoice()->data;

        $data['export'] = [
            'foreign_trade' => 'EXPORTADOR',
            'incoterm' => 'FOB',
            'incoterm_place' => 'GUAYAQUIL',
            'origin_country' => '593',
            'shipment_port' => 'GUAYAQUIL',
            'destination_port' => 'MIAMI',
            'destination_country' => '840',
            'acquisition_country' => '593',
            'incoterm_subtotal' => 'FOB',
            'international_freight' => '0.00',
            'international_insurance' => '0.00',
            'customs_expenses' => '0.00',
            'other_transport_expenses' => '0.00',
        ];

        $data['plate'] = 'PBA1234';

        $data['reimbursements'] = [
            [
                'provider_identification_type' => '04',
                'provider_identification_number' => '1790012345001',
                'provider_type' => '01',
                'document_code' => '01',
                'establishment_code' => '001',
                'emission_point_code' => '001',
                'sequential' => '000000001',
                'emission_date' => '13/05/2026',
                'authorization_number' => '1305202601179001234500110010010000000101234567818',
                'taxes' => [
                    ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '10.00', 'value' => '1.50'],
                ],
            ],
        ];

        $data['substitute_delivery_guide'] = [
            'start_address' => 'Quito',
            'destination_address' => 'Guayaquil',
            'transport_start_date' => '13/05/2026',
            'transport_end_date' => '14/05/2026',
            'carrier_name' => 'TRANSPORTISTA TEST',
            'carrier_identification_type' => '04',
            'carrier_ruc' => '1790012345001',
            'plate' => 'PBA1234',
            'destinations' => [
                [
                    'reason' => 'Venta',
                    'destination_establishment' => '001',
                    'route' => 'Quito - Guayaquil',
                ],
            ],
        ];

        $data['third_party_items'] = [
            ['concept' => 'Servicio de terceros', 'total' => '5.00'],
        ];

        $data['fiscal_machine'] = [
            'brand' => 'MARCA',
            'model' => 'MODELO',
            'serial' => 'SERIE123',
        ];

        return XmlGenerationData::make(
            documentType: XmlDocumentType::Invoice,
            environment: XmlEnvironment::Testing,
            accessKey: '1305202601179001234500110010010000000251234567817',
            data: $data,
        );
    }
}
