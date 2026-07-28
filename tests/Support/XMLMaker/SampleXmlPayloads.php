<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Support\XMLMaker;

use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;

/**
 * Representative, schema-valid payloads for every supported SRI document type.
 *
 * Shared by the XSD conformance test so each document type is exercised with a
 * realistic payload that covers its distinctive sections.
 */
final class SampleXmlPayloads
{
    /**
     * Returns one representative payload per document type.
     *
     * @return array<string, XmlGenerationData>
     */
    public static function all(): array
    {
        return [
            'invoice' => self::invoice(),
            'purchase-settlement' => self::purchaseSettlement(),
            'credit-note' => self::creditNote(),
            'debit-note' => self::debitNote(),
            'delivery-guide' => self::deliveryGuide(),
            'withholding-receipt' => self::withholdingReceipt(),
        ];
    }

    public static function invoice(): XmlGenerationData
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
                    'special_taxpayer_number' => '123',
                ],
                'establishment' => ['code' => '001'],
                'emission_point' => ['code' => '001'],
                'customer' => [
                    'identification_type' => '05',
                    'identification_number' => '1710034065',
                    'name' => 'CONSUMIDOR FINAL',
                    'address' => 'Quito',
                ],
                'establishment_address' => 'Quito',
                'requires_accounting' => 'NO',
                'delivery_guide' => '001-001-000000001',
                'total_without_taxes' => '10.00',
                'total_subsidy' => '0.00',
                'total_discount' => '0.00',
                'reimbursement' => [
                    'document_code' => '41',
                    'total' => '0.00',
                    'taxable_base_total' => '0.00',
                    'tax_total' => '0.00',
                ],
                'tax_totals' => [
                    [
                        'code' => '2',
                        'percentage_code' => '4',
                        'additional_discount' => '0.00',
                        'taxable_base' => '10.00',
                        'rate' => '15.00',
                        'value' => '1.50',
                        'refund_value' => '0.00',
                    ],
                ],
                'tip' => '0.00',
                'total_amount' => '11.50',
                'currency' => 'DOLAR',
                'total_iva_amount' => '0.00',
                'total_renta_amount' => '0.00',
                'payments' => [
                    ['method' => '01', 'total' => '11.50'],
                ],
                'details' => [
                    [
                        'main_code' => 'P001',
                        'description' => 'Producto de prueba',
                        'unit' => 'UND',
                        'quantity' => '1.00',
                        'unit_price' => '10.00',
                        'unit_price_without_subsidy' => '12.00',
                        'discount' => '0.00',
                        'total_without_tax' => '10.00',
                        'additional_info' => ['Color' => 'Azul'],
                        'taxes' => [
                            [
                                'code' => '2',
                                'percentage_code' => '4',
                                'rate' => '15.00',
                                'taxable_base' => '10.00',
                                'value' => '1.50',
                                'refund_value' => '0.00',
                            ],
                        ],
                    ],
                ],
                'additional_info' => ['Email' => 'cliente@example.com'],
            ],
        );
    }

    public static function purchaseSettlement(): XmlGenerationData
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
                'establishment' => ['code' => '001'],
                'emission_point' => ['code' => '001'],
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
                    ['code' => '2', 'percentage_code' => '4', 'taxable_base' => '10.00', 'value' => '1.50'],
                ],
                'total_amount' => '11.50',
                'currency' => 'DOLAR',
                'payments' => [
                    ['method' => '01', 'total' => '11.50'],
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
                        'additional_info' => ['Batch' => 'A1'],
                        'taxes' => [
                            ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '10.00', 'value' => '1.50'],
                        ],
                    ],
                ],
                'additional_info' => ['Email' => 'provider@example.com'],
            ],
        );
    }

    public static function creditNote(): XmlGenerationData
    {
        return XmlGenerationData::make(
            documentType: XmlDocumentType::CreditNote,
            environment: XmlEnvironment::Testing,
            accessKey: '1305202604179001234500110010010000000251234567814',
            data: [
                'date' => '13/05/2026',
                'sequential' => '000000025',
                'company' => [
                    'ruc' => '1790012345001',
                    'legal_name' => 'MTZ TEST S.A.',
                    'head_office_address' => 'Quito',
                    'special_taxpayer_number' => '123',
                ],
                'establishment' => ['code' => '001'],
                'emission_point' => ['code' => '001'],
                'customer' => [
                    'identification_type' => '05',
                    'identification_number' => '1710034065',
                    'name' => 'CLIENTE TEST',
                ],
                'establishment_address' => 'Quito',
                'requires_accounting' => 'NO',
                'referenced_document' => [
                    'document_type' => '01',
                    'number' => '001-001-000000024',
                    'emission_date' => '12/05/2026',
                    'reason' => 'Devolucion',
                ],
                'total_without_taxes' => '10.00',
                'modified_document_total' => '11.50',
                'currency' => 'DOLAR',
                'tax_totals' => [
                    ['code' => '2', 'percentage_code' => '4', 'taxable_base' => '10.00', 'value' => '1.50'],
                ],
                'details' => [
                    [
                        'main_code' => 'P001',
                        'description' => 'Producto',
                        'quantity' => '1.00',
                        'unit_price' => '10.00',
                        'discount' => '0.00',
                        'total_without_tax' => '10.00',
                        'taxes' => [
                            ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '10.00', 'value' => '1.50'],
                        ],
                    ],
                ],
            ],
        );
    }

    public static function debitNote(): XmlGenerationData
    {
        return XmlGenerationData::make(
            documentType: XmlDocumentType::DebitNote,
            environment: XmlEnvironment::Testing,
            accessKey: '1305202605179001234500110010010000000251234567810',
            data: [
                'date' => '13/05/2026',
                'sequential' => '000000025',
                'company' => [
                    'ruc' => '1790012345001',
                    'legal_name' => 'MTZ TEST S.A.',
                    'head_office_address' => 'Quito',
                    'special_taxpayer_number' => '123',
                ],
                'establishment' => ['code' => '001'],
                'emission_point' => ['code' => '001'],
                'customer' => [
                    'identification_type' => '05',
                    'identification_number' => '1710034065',
                    'name' => 'CLIENTE TEST',
                ],
                'referenced_document' => [
                    'document_type' => '01',
                    'number' => '001-001-000000024',
                    'emission_date' => '12/05/2026',
                ],
                'establishment_address' => 'Quito',
                'requires_accounting' => 'NO',
                'total_without_taxes' => '10.00',
                'tax_totals' => [
                    ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '10.00', 'value' => '1.50'],
                ],
                'total_amount' => '11.50',
                'payments' => [
                    ['method' => '01', 'total' => '11.50'],
                ],
                'reasons' => [
                    ['reason' => 'Interes por mora', 'amount' => '10.00'],
                ],
            ],
        );
    }

    public static function deliveryGuide(): XmlGenerationData
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
                'establishment' => ['code' => '001'],
                'emission_point' => ['code' => '001'],
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
                                'additional_info' => ['Lote' => 'A1'],
                            ],
                        ],
                    ],
                ],
            ],
        );
    }

    public static function withholdingReceipt(): XmlGenerationData
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
                'establishment' => ['code' => '001'],
                'emission_point' => ['code' => '001'],
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
                            ['code' => '2', 'percentage_code' => '4', 'taxable_base' => '100.00', 'rate' => '15.00', 'value' => '15.00'],
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
                                    ['code' => '2', 'percentage_code' => '4', 'rate' => '15.00', 'taxable_base' => '10.00', 'value' => '1.50'],
                                ],
                            ],
                        ],
                        'payments' => [
                            ['method' => '01', 'total' => '115.00'],
                        ],
                    ],
                ],
            ],
        );
    }
}
