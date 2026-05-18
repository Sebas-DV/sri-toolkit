<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Feature\XMLMaker;

use DOMDocument;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
use MTZ\Toolkit\XMLMaker\XMLMaker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XMLMakerTest extends TestCase
{
    #[Test]
    public function it_generates_invoice_xml_from_public_api(): void
    {
        $result = (new XMLMaker())->generate(
            XmlGenerationData::make(
                documentType: XmlDocumentType::Invoice,
                environment: XmlEnvironment::Testing,
                accessKey: '1305202601179001234500110010010000000251234567817',
                data: $this->invoicePayload(),
            ),
        );

        $xml = $result->toString();

        $this->assertStringContainsString('<factura id="comprobante" version="1.1.0">', $xml);
        $this->assertStringContainsString('<infoTributaria>', $xml);
        $this->assertStringContainsString('<infoFactura>', $xml);
        $this->assertStringContainsString('<detalles>', $xml);

        $document = new DOMDocument();

        $this->assertTrue($document->loadXML($xml));
    }

    private function invoicePayload(): array
    {
        return [
            'date' => '13/05/2026',
            'sequential' => '000000025',
            'company' => [
                'ruc' => '1790012345001',
                'legal_name' => 'MTZ TEST S.A.',
                'trade_name' => 'MTZ TEST',
                'head_office_address' => 'Quito',
            ],
            'establishment' => [
                'code' => '001',
            ],
            'emission_point' => [
                'code' => '001',
            ],
            'customer' => [
                'identification_type' => '05',
                'identification_number' => '1710034065',
                'name' => 'CONSUMIDOR FINAL',
                'address' => 'Quito',
            ],
            'establishment_address' => 'Quito',
            'requires_accounting' => 'NO',
            'total_without_taxes' => '10.00',
            'total_discount' => '0.00',
            'tax_totals' => [
                [
                    'code' => '2',
                    'percentage_code' => '4',
                    'taxable_base' => '10.00',
                    'value' => '1.50',
                ],
            ],
            'tip' => '0.00',
            'total_amount' => '11.50',
            'currency' => 'DOLAR',
            'payments' => [
                [
                    'method' => '01',
                    'total' => '11.50',
                ],
            ],
            'details' => [
                [
                    'main_code' => 'P001',
                    'description' => 'Producto de prueba',
                    'quantity' => '1.00',
                    'unit_price' => '10.00',
                    'discount' => '0.00',
                    'total_without_tax' => '10.00',
                    'taxes' => [
                        [
                            'code' => '2',
                            'percentage_code' => '4',
                            'rate' => '15.00',
                            'taxable_base' => '10.00',
                            'value' => '1.50',
                        ],
                    ],
                ],
            ],
        ];
    }
}
