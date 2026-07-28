<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\RideGenerator;

use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\Renders\TwigRideTemplateRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TwigRideTemplateRendererTest extends TestCase
{
    private const ACCESS_KEY = '1305202601179001234500120020030000000251234567815';
    private const PURCHASE_SETTLEMENT_ACCESS_KEY = '1703202403176001321000120010019100012804071614910';
    private const CREDIT_NOTE_ACCESS_KEY = '0104202404176001321000110012220000002361234567819';
    private const DEBIT_NOTE_ACCESS_KEY = '1803202405176001321000110010010000000161234567819';
    private const DELIVERY_GUIDE_ACCESS_KEY = '1509201706176001321000110012220000000491234567815';
    private const WITHHOLDING_ACCESS_KEY = '1509201707176001321000110012220000024031234567811';

    #[Test]
    public function it_renders_the_invoice_design_with_real_data_and_an_embedded_logo(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::Invoice,
                accessKey: self::ACCESS_KEY,
                data: $this->invoiceData(withLogo: true),
                authorizationNumber: self::ACCESS_KEY,
                authorizationDate: '13/05/2026 10:30:00',
            ),
        );

        $this->assertStringContainsString('RAZÓN SOCIAL REAL S.A.', $html);
        $this->assertStringContainsString('MARCA REAL', $html);
        $this->assertStringContainsString('CLIENTE &lt;REAL&gt;', $html);
        $this->assertStringContainsString('002-003-000000025', $html);
        $this->assertStringContainsString('Color: Azul', $html);
        $this->assertStringContainsString('SUBTOTAL 15%', $html);
        $this->assertStringContainsString('15.00', $html);
        $this->assertStringContainsString('01 - SIN UTILIZACIÓN DEL SISTEMA FINANCIERO', $html);
        $this->assertStringContainsString(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB',
            $html,
        );
        $this->assertStringNotContainsString('NO LOGO', $html);
        $this->assertStringNotContainsString('PRUEBA FANTASIA 2', $html);
    }

    #[Test]
    public function it_renders_the_no_logo_variant_when_the_company_has_no_logo(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::Invoice,
                accessKey: self::ACCESS_KEY,
                data: $this->invoiceData(withLogo: false),
            ),
        );

        $this->assertStringContainsString(
            '<span class="invoice-no-logo">NO LOGO</span>',
            $html,
        );
    }

    #[Test]
    public function it_renders_the_purchase_settlement_with_its_own_header_and_real_provider_data(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::PurchaseSettlement,
                accessKey: self::PURCHASE_SETTLEMENT_ACCESS_KEY,
                data: $this->purchaseSettlementData(withLogo: true),
                authorizationNumber: self::PURCHASE_SETTLEMENT_ACCESS_KEY,
                authorizationDate: '17/03/2024 14:33:35',
            ),
        );

        $this->assertStringContainsString('class="purchase-settlement-header"', $html);
        $this->assertStringContainsString(
            'LIQUIDACIÓN DE COMPRA DE BIENES Y PRESTACIÓN DE SERVICIOS',
            $html,
        );
        $this->assertStringContainsString('PROVEEDOR &lt;REAL&gt;', $html);
        $this->assertStringContainsString('IVA5', $html);
        $this->assertStringContainsString('IVA15ESP', $html);
        $this->assertStringContainsString('Color: Azul', $html);
        $this->assertStringContainsString('SUBTOTAL 5%', $html);
        $this->assertStringContainsString('SUBTOTAL 15%', $html);
        $this->assertStringContainsString('IVA 5%', $html);
        $this->assertStringContainsString('IVA 15%', $html);
        $this->assertStringContainsString('$ 369.50', $html);
        $this->assertStringContainsString(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB',
            $html,
        );
        $this->assertStringNotContainsString('class="invoice-header"', $html);
        $this->assertStringNotContainsString('class="credit-note-header"', $html);
    }

    #[Test]
    public function it_renders_the_purchase_settlement_no_logo_variant(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::PurchaseSettlement,
                accessKey: self::PURCHASE_SETTLEMENT_ACCESS_KEY,
                data: $this->purchaseSettlementData(withLogo: false),
            ),
        );

        $this->assertStringContainsString(
            '<span class="purchase-settlement-no-logo">NO LOGO</span>',
            $html,
        );
    }

    #[Test]
    public function it_renders_the_credit_note_with_its_own_header_styles_and_real_data(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::CreditNote,
                accessKey: self::CREDIT_NOTE_ACCESS_KEY,
                data: $this->creditNoteData(withLogo: true),
                authorizationNumber: self::CREDIT_NOTE_ACCESS_KEY,
                authorizationDate: '01/04/2024 12:09:26',
            ),
        );

        $this->assertStringContainsString('class="credit-note-header"', $html);
        $this->assertStringContainsString('NOTA DE CRÉDITO', $html);
        $this->assertStringContainsString('CLIENTE &lt;NOTA CRÉDITO&gt;', $html);
        $this->assertStringContainsString('001-004-000123456', $html);
        $this->assertStringContainsString('DEVOLUCIÓN DE MERCADERÍA', $html);
        $this->assertStringContainsString('Color: Azul', $html);
        $this->assertStringContainsString('SUBTOTAL 15%', $html);
        $this->assertStringContainsString('IVA 15%', $html);
        $this->assertStringContainsString('$ 115.00', $html);
        $this->assertStringContainsString(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB',
            $html,
        );
        $this->assertStringNotContainsString('class="invoice-header"', $html);
        $this->assertStringNotContainsString('PRUEBAS SERVICIO DE RENTAS', $html);
    }

    #[Test]
    public function it_renders_the_credit_note_no_logo_variant(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::CreditNote,
                accessKey: self::CREDIT_NOTE_ACCESS_KEY,
                data: $this->creditNoteData(withLogo: false),
            ),
        );

        $this->assertStringContainsString(
            '<span class="credit-note-no-logo">NO LOGO</span>',
            $html,
        );
    }

    #[Test]
    public function it_renders_the_debit_note_with_its_own_header_styles_and_real_data(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::DebitNote,
                accessKey: self::DEBIT_NOTE_ACCESS_KEY,
                data: $this->debitNoteData(withLogo: true),
                authorizationNumber: self::DEBIT_NOTE_ACCESS_KEY,
                authorizationDate: '21/03/2024 16:42:46',
            ),
        );

        $this->assertStringContainsString('class="debit-note-header"', $html);
        $this->assertStringContainsString('NOTA DE DÉBITO', $html);
        $this->assertStringContainsString('CLIENTE &lt;NOTA DÉBITO&gt;', $html);
        $this->assertStringContainsString('FACTURA: 001-001-000000002', $html);
        $this->assertStringContainsString('INTERÉS POR MORA', $html);
        $this->assertStringContainsString('01 - SIN UTILIZACIÓN DEL SISTEMA FINANCIERO', $html);
        $this->assertStringContainsString('SUBTOTAL 15%', $html);
        $this->assertStringContainsString('IVA 15%', $html);
        $this->assertStringContainsString('$ 115.00', $html);
        $this->assertStringContainsString(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB',
            $html,
        );
        $this->assertStringNotContainsString('class="invoice-header"', $html);
        $this->assertStringNotContainsString('class="credit-note-header"', $html);
    }

    #[Test]
    public function it_renders_the_debit_note_no_logo_variant(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::DebitNote,
                accessKey: self::DEBIT_NOTE_ACCESS_KEY,
                data: $this->debitNoteData(withLogo: false),
            ),
        );

        $this->assertStringContainsString(
            '<span class="debit-note-no-logo">NO LOGO</span>',
            $html,
        );
    }

    #[Test]
    public function it_renders_the_delivery_guide_with_its_own_header_and_real_recipient_data(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::DeliveryGuide,
                accessKey: self::DELIVERY_GUIDE_ACCESS_KEY,
                data: $this->deliveryGuideData(withLogo: true),
                authorizationNumber: self::DELIVERY_GUIDE_ACCESS_KEY,
                authorizationDate: '15/09/2017 12:34:27',
            ),
        );

        $this->assertStringContainsString('class="delivery-guide-header"', $html);
        $this->assertStringContainsString('GUÍA DE REMISIÓN', $html);
        $this->assertStringContainsString('TRANSPORTISTA &lt;REAL&gt;', $html);
        $this->assertStringContainsString('DESTINATARIO &lt;REAL&gt;', $html);
        $this->assertStringContainsString('FACTURA', $html);
        $this->assertStringContainsString('002-003-123456789', $html);
        $this->assertStringContainsString('1305202601179001234500110010010000000251234567817', $html);
        $this->assertStringContainsString('Agencia Tumbaco', $html);
        $this->assertStringContainsString('CALEFÓN', $html);
        $this->assertStringContainsString('ICE001', $html);
        $this->assertStringContainsString(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB',
            $html,
        );
        $this->assertStringNotContainsString('class="invoice-header"', $html);
        $this->assertStringNotContainsString('class="withholding-receipt-header"', $html);
    }

    #[Test]
    public function it_renders_the_delivery_guide_no_logo_variant(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::DeliveryGuide,
                accessKey: self::DELIVERY_GUIDE_ACCESS_KEY,
                data: $this->deliveryGuideData(withLogo: false),
            ),
        );

        $this->assertStringContainsString(
            '<span class="delivery-guide-no-logo">NO LOGO</span>',
            $html,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function deliveryGuideData(bool $withLogo): array
    {
        $company = [
            'ruc' => '1760013210001',
            'legal_name' => 'RAZÓN SOCIAL GUÍA S.A.',
            'trade_name' => 'MARCA GUÍA',
            'head_office_address' => 'Quito',
            'requires_accounting' => 'SI',
        ];

        if ($withLogo)
        {
            $company['logo_base64'] = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB';
        }

        return [
            'company' => $company,
            'establishment_code' => '001',
            'emission_point_code' => '222',
            'sequential' => '000000049',
            'establishment_address' => 'Salinas y Santiago',
            'carrier' => [
                'identification_number' => '1760013210001',
                'name' => 'TRANSPORTISTA <REAL>',
                'plate' => 'ZZZ01234',
            ],
            'shipping' => [
                'start_address' => 'Agencia San Rafael',
                'start_date' => '15/09/2017',
                'end_date' => '15/09/2017',
            ],
            'recipients' => [
                [
                    'identification_number' => '1760013210001',
                    'name' => 'DESTINATARIO <REAL>',
                    'destination_address' => 'Agencia Tumbaco',
                    'reason' => 'Venta',
                    'customs_document' => '-',
                    'destination_establishment_code' => '-',
                    'supporting_document_code' => '01',
                    'supporting_document_number' => '002003123456789',
                    'supporting_document_authorization' => '1305202601179001234500110010010000000251234567817',
                    'supporting_document_emission_date' => '10/09/2017',
                    'route' => 'San Rafael - Tumbaco',
                    'details' => [
                        [
                            'main_code' => 'ICE001',
                            'auxiliary_code' => '-',
                            'description' => 'CALEFÓN',
                            'quantity' => '1',
                        ],
                    ],
                ],
            ],
            'additional_info' => [
                'Teléfono' => '022***28',
                'Email' => 'transportista@example.com',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function purchaseSettlementData(bool $withLogo): array
    {
        $company = [
            'ruc' => '1760013210001',
            'legal_name' => 'RAZÓN SOCIAL LIQUIDACIÓN S.A.',
            'trade_name' => 'MARCA LIQUIDACIÓN',
            'head_office_address' => 'Quito',
            'requires_accounting' => 'SI',
            'withholding_agent_resolution' => '1',
        ];

        if ($withLogo)
        {
            $company['logo_base64'] = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB';
        }

        return [
            'company' => $company,
            'establishment_code' => '001',
            'emission_point_code' => '001',
            'sequential' => '910001280',
            'date' => '17/03/2024',
            'establishment_address' => 'Salinas y Santiago',
            'provider' => [
                'identification_number' => '1760013210001',
                'name' => 'PROVEEDOR <REAL>',
                'address' => 'Quito',
            ],
            'details' => [
                [
                    'main_code' => 'IVA5',
                    'quantity' => '1.00',
                    'description' => 'IVA5',
                    'additional_info' => ['Color' => 'Azul'],
                    'unit_price' => '100.00',
                    'discount' => '0.00',
                    'total_without_tax' => '100.00',
                ],
                [
                    'main_code' => 'IVA15',
                    'quantity' => '1.00',
                    'description' => 'IVA15',
                    'unit_price' => '180.00',
                    'discount' => '0.00',
                    'total_without_tax' => '180.00',
                ],
                [
                    'main_code' => 'IVA15ESP',
                    'quantity' => '1.00',
                    'description' => 'IVA15ESP',
                    'unit_price' => '50.00',
                    'discount' => '0.00',
                    'total_without_tax' => '50.00',
                ],
            ],
            'payments' => [
                ['method' => '01', 'total' => '369.50'],
            ],
            'tax_totals' => [
                [
                    'code' => '2',
                    'rate' => '5.00',
                    'taxable_base' => '100.00',
                    'value' => '5.00',
                ],
                [
                    'code' => '2',
                    'rate' => '15.00',
                    'taxable_base' => '230.00',
                    'value' => '34.50',
                ],
            ],
            'additional_info' => [
                'Teléfono' => '022***28',
                'Email' => 'proveedor@example.com',
            ],
            'total_without_taxes' => '330.00',
            'total_discount' => '0.00',
            'total_amount' => '369.50',
        ];
    }

    #[Test]
    public function it_renders_the_withholding_receipt_with_its_own_header_and_flattened_real_data(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::WithholdingReceipt,
                accessKey: self::WITHHOLDING_ACCESS_KEY,
                data: $this->withholdingReceiptData(withLogo: true),
                authorizationNumber: self::WITHHOLDING_ACCESS_KEY,
                authorizationDate: '15/09/2017 16:42:46',
            ),
        );

        $this->assertStringContainsString('class="withholding-receipt-header"', $html);
        $this->assertStringContainsString('COMPROBANTE DE RETENCIÓN', $html);
        $this->assertStringContainsString('PROVEEDOR &lt;REAL&gt;', $html);
        $this->assertStringContainsString('001-001-000000025', $html);
        $this->assertStringContainsString('05/2026', $html);
        $this->assertStringContainsString('FACTURA', $html);
        $this->assertStringContainsString('RENTA', $html);
        $this->assertStringContainsString('IVA', $html);
        $this->assertStringContainsString('100.00', $html);
        $this->assertStringContainsString('30.00', $html);
        $this->assertStringContainsString('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB', $html);
        $this->assertStringNotContainsString('class="invoice-header"', $html);
        $this->assertStringNotContainsString('class="credit-note-header"', $html);
        $this->assertStringNotContainsString('class="debit-note-header"', $html);
    }

    #[Test]
    public function it_renders_the_withholding_receipt_no_logo_variant(): void
    {
        $html = (new TwigRideTemplateRenderer())->render(
            RideData::make(
                documentType: RideDocumentType::WithholdingReceipt,
                accessKey: self::WITHHOLDING_ACCESS_KEY,
                data: $this->withholdingReceiptData(withLogo: false),
            ),
        );

        $this->assertStringContainsString(
            '<span class="withholding-receipt-no-logo">NO LOGO</span>',
            $html,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function withholdingReceiptData(bool $withLogo): array
    {
        $company = [
            'ruc' => '1760013210001',
            'legal_name' => 'RAZÓN SOCIAL RETENCIÓN S.A.',
            'trade_name' => 'MARCA RETENCIÓN',
            'head_office_address' => 'Quito',
            'requires_accounting' => 'SI',
            'withholding_agent_resolution' => '1',
        ];

        if ($withLogo)
        {
            $company['logo_base64'] = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB';
        }

        return [
            'company' => $company,
            'establishment_code' => '001',
            'emission_point_code' => '222',
            'sequential' => '000002403',
            'date' => '15/09/2017',
            'establishment_address' => 'Quito',
            'subject' => [
                'identification_number' => '1790012345001',
                'name' => 'PROVEEDOR <REAL>',
                'address' => 'Guayaquil',
            ],
            'fiscal_period' => '05/2026',
            'supporting_documents' => [
                [
                    'document_code' => '01',
                    'document_number' => '001001000000025',
                    'emission_date' => '13/05/2026',
                    'total_without_taxes' => '100.00',
                    'withholdings' => [
                        [
                            'code' => '1',
                            'withholding_code' => '303',
                            'taxable_base' => '100.00',
                            'percentage' => '10.00',
                            'value' => '10.00',
                        ],
                        [
                            'code' => '2',
                            'withholding_code' => '1',
                            'taxable_base' => '100.00',
                            'percentage' => '30.00',
                            'value' => '30.00',
                        ],
                    ],
                ],
            ],
            'additional_info' => ['Email' => 'proveedor@example.com'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function debitNoteData(bool $withLogo): array
    {
        $company = [
            'ruc' => '1760013210001',
            'legal_name' => 'RAZÓN SOCIAL DÉBITO S.A.',
            'trade_name' => 'MARCA DÉBITO',
            'head_office_address' => 'Quito',
            'requires_accounting' => 'SI',
            'withholding_agent_resolution' => '1',
        ];

        if ($withLogo)
        {
            $company['logo_base64'] = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB';
        }

        return [
            'company' => $company,
            'establishment_code' => '001',
            'emission_point_code' => '001',
            'sequential' => '000000016',
            'date' => '18/03/2024',
            'establishment_address' => 'Quito',
            'customer' => [
                'identification_number' => '1760013210001',
                'name' => 'CLIENTE <NOTA DÉBITO>',
                'address' => 'Quito',
            ],
            'referenced_document' => [
                'document_type' => '01',
                'number' => '001-001-000000002',
                'emission_date' => '18/03/2024',
            ],
            'reasons' => [
                ['reason' => 'INTERÉS POR MORA', 'amount' => '100.00'],
            ],
            'payments' => [
                ['method' => '01', 'total' => '115.00'],
            ],
            'tax_totals' => [
                [
                    'code' => '2',
                    'percentage_code' => '4',
                    'rate' => '15.00',
                    'taxable_base' => '100.00',
                    'value' => '15.00',
                ],
            ],
            'additional_info' => ['Email' => 'facturador@example.com'],
            'total_without_taxes' => '100.00',
            'total_amount' => '115.00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function creditNoteData(bool $withLogo): array
    {
        $company = [
            'ruc' => '1760013210001',
            'legal_name' => 'RAZÓN SOCIAL NOTA S.A.',
            'trade_name' => 'MARCA NOTA',
            'head_office_address' => 'Quito',
            'requires_accounting' => 'SI',
        ];

        if ($withLogo)
        {
            $company['logo_base64'] = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB';
        }

        return [
            'company' => $company,
            'establishment_code' => '001',
            'emission_point_code' => '222',
            'sequential' => '000000236',
            'date' => '01/04/2024',
            'establishment_address' => 'Salinas y Santiago',
            'customer' => [
                'identification_number' => '1760013210001',
                'name' => 'CLIENTE <NOTA CRÉDITO>',
                'address' => 'Guayaquil',
            ],
            'referenced_document' => [
                'document_type' => '01',
                'number' => '001-004-000123456',
                'emission_date' => '01/04/2024',
                'reason' => 'DEVOLUCIÓN DE MERCADERÍA',
            ],
            'details' => [
                [
                    'main_code' => 'P001',
                    'quantity' => '1.00',
                    'description' => 'Producto devuelto',
                    'additional_info' => ['Color' => 'Azul'],
                    'unit_price' => '100.00',
                    'discount' => '0.00',
                    'total_without_tax' => '100.00',
                    'taxes' => [
                        [
                            'code' => '2',
                            'percentage_code' => '4',
                            'rate' => '15.00',
                            'taxable_base' => '100.00',
                            'value' => '15.00',
                        ],
                    ],
                ],
            ],
            'tax_totals' => [
                [
                    'code' => '2',
                    'percentage_code' => '4',
                    'taxable_base' => '100.00',
                    'value' => '15.00',
                ],
            ],
            'additional_info' => ['Email' => 'cliente@example.com'],
            'total_without_taxes' => '100.00',
            'modified_document_total' => '115.00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceData(bool $withLogo): array
    {
        $company = [
            'ruc' => '1790012345001',
            'legal_name' => 'RAZÓN SOCIAL REAL S.A.',
            'trade_name' => 'MARCA REAL',
            'head_office_address' => 'Quito',
            'requires_accounting' => 'SI',
        ];

        if ($withLogo)
        {
            $company['logo_base64'] = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB';
        }

        return [
            'company' => $company,
            'establishment' => ['code' => '002'],
            'emission_point' => ['code' => '003'],
            'sequential' => '000000025',
            'date' => '13/05/2026',
            'establishment_address' => 'Quito',
            'customer' => [
                'identification_number' => '1710034065',
                'name' => 'CLIENTE <REAL>',
                'address' => 'Guayaquil',
            ],
            'details' => [
                [
                    'main_code' => 'P001',
                    'quantity' => '1.00',
                    'description' => 'Producto real',
                    'additional_info' => ['Color' => 'Azul'],
                    'unit_price' => '100.00',
                    'discount' => '0.00',
                    'total_without_tax' => '100.00',
                ],
            ],
            'payments' => [
                ['method' => '01', 'total' => '115.00'],
            ],
            'tax_totals' => [
                [
                    'code' => '2',
                    'rate' => '15.00',
                    'taxable_base' => '100.00',
                    'value' => '15.00',
                ],
            ],
            'total_without_taxes' => '100.00',
            'total_discount' => '0.00',
            'total_amount' => '115.00',
        ];
    }
}
