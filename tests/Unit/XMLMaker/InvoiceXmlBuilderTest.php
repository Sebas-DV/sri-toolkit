<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use DOMXPath;
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

        $this->assertStringContainsString('<factura id="comprobante" version="1.1.0">', $xml);
        $this->assertStringContainsString('<ambiente>1</ambiente>', $xml);
        $this->assertStringContainsString('<codDoc>01</codDoc>', $xml);
        $this->assertStringContainsString('<claveAcceso>1305202601179001234500110010010000000251234567817</claveAcceso>', $xml);
        $this->assertStringContainsString('<razonSocialComprador>CONSUMIDOR FINAL</razonSocialComprador>', $xml);
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
        return XmlGenerationData::make(
            documentType: XmlDocumentType::Invoice,
            environment: XmlEnvironment::Testing,
            accessKey: '1305202601179001234500110010010000000251234567817',
            data: [
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
                'additional_info' => [
                    'Email' => 'cliente@example.com',
                ],
            ],
        );
    }
}
