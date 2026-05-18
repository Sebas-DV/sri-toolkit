<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\XMLMaker;

use DOMDocument;
use DOMException;
use MTZ\Toolkit\XMLMaker\Builders\WithholdingReceiptXmlBuilder;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
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
        return XmlGenerationData::make(
            documentType: XmlDocumentType::WithholdingReceipt,
            environment: XmlEnvironment::Testing,
            accessKey: '1305202607179001234500110010010000000251234567816',
            data: [
                'date' => '13/05/2026',
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
                'establishment_address' => 'Quito',
                'requires_accounting' => 'NO',
                'subject' => [
                    'identification_type' => '04',
                    'subject_type' => '01',
                    'related_party' => 'NO',
                    'name' => 'PROVEEDOR TEST S.A.',
                    'identification_number' => '1790012345001',
                ],
                'fiscal_period' => '05/2026',
                'supporting_documents' => [
                    [
                        'support_code' => '01',
                        'document_code' => '01',
                        'document_number' => '001001000000025',
                        'emission_date' => '13/05/2026',
                        'accounting_record_date' => '13/05/2026',
                        'authorization_number' => '1305202601179001234500110010010000000251234567817',
                        'local_or_foreign_payment' => '01',
                        'total_without_taxes' => '100.00',
                        'total_amount' => '115.00',
                        'taxes' => [
                            [
                                'code' => '2',
                                'percentage_code' => '4',
                                'taxable_base' => '100.00',
                                'rate' => '15.00',
                                'value' => '15.00',
                            ],
                        ],
                        'withholdings' => [
                            [
                                'code' => '1',
                                'withholding_code' => '303',
                                'taxable_base' => '100.00',
                                'percentage' => '10.00',
                                'value' => '10.00',
                                'dividends' => [
                                    'payment_date' => '13/05/2026',
                                    'corporate_income_tax' => '0.00',
                                    'fiscal_year_profit' => '2026',
                                ],
                                'banana_purchase' => [
                                    'box_count' => '1',
                                    'box_price' => '10.00',
                                ],
                            ],
                        ],
                        'reimbursements' => [
                            [
                                'provider_identification_type' => '04',
                                'provider_identification_number' => '1790012345001',
                                'provider_type' => '01',
                                'document_code' => '01',
                                'establishment_code' => '001',
                                'emission_point_code' => '001',
                                'sequential' => '000000010',
                                'emission_date' => '13/05/2026',
                                'authorization_number' => '1305202601179001234500110010010000000101234567818',
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
                        'payments' => [
                            [
                                'method' => '01',
                                'total' => '115.00',
                            ],
                        ],
                    ],
                ],
            ],
        );
    }
}
