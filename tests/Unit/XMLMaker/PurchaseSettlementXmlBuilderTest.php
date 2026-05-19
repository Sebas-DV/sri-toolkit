<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use DOMXPath;
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
        return XmlGenerationData::make(
            documentType: XmlDocumentType::PurchaseSettlement,
            environment: XmlEnvironment::Testing,
            accessKey: '1305202603179001234500110010010000000301234567811',
            data: [
                'date' => '13/05/2026',
                'sequential' => '000000030',
                'company' => [
                    'ruc' => '1790012345001',
                    'legal_name' => 'MTZ TEST S.A.',
                    'trade_name' => 'MTZ TEST',
                    'head_office_address' => 'Quito',
                    'special_taxpayer_number' => '123',
                ],
                'establishment' => [
                    'code' => '001',
                ],
                'emission_point' => [
                    'code' => '001',
                ],
                'provider' => [
                    'identification_type' => '05',
                    'identification_number' => '0102030405',
                    'name' => 'PROVIDER TEST',
                    'address' => 'Cuenca',
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
                        'description' => 'Purchased service',
                        'unit' => 'UND',
                        'quantity' => '1.00',
                        'unit_price' => '10.00',
                        'discount' => '0.00',
                        'total_without_tax' => '10.00',
                        'additional_info' => [
                            'Batch' => 'A1',
                        ],
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
                'additional_info' => [
                    'Email' => 'provider@example.com',
                ],
            ],
        );
    }
}
