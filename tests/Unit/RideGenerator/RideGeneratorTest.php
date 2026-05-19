<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Tests\Unit\RideGenerator;

use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\RideGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RideGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_a_pdf_for_every_ride_document_type(): void
    {
        $generator = new RideGenerator();

        foreach ($this->documents() as $document)
        {
            $pdf = $generator->generate(
                RideData::make(
                    documentType: $document['type'],
                    accessKey: '1305202601179001234500110010010000000251234567817',
                    data: $document['data'],
                    authorizationNumber: '1305202601179001234500110010010000000251234567817',
                    authorizationDate: '13/05/2026 10:30:00',
                ),
            );

            $this->assertStringStartsWith('%PDF', $pdf->content);
            $this->assertStringEndsWith('.pdf', $pdf->filename);
            $this->assertGreaterThan(1000, strlen($pdf->content));
        }
    }

    /**
     * @return list<array{type: RideDocumentType, data: array<string, mixed>}>
     */
    private function documents(): array
    {
        $common = [
            'company' => [
                'ruc' => '1790012345001',
                'legal_name' => 'MTZ TEST S.A.',
                'head_office_address' => 'Quito',
                'special_taxpayer' => '123',
            ],
            'establishment_code' => '001',
            'emission_point_code' => '001',
            'establishment_address' => 'Quito',
            'requires_accounting' => 'SI',
            'environment_label' => 'PRUEBAS',
            'emission_label' => 'NORMAL',
            'additional_info' => [
                'Email' => 'cliente@example.com',
            ],
        ];

        $customer = [
            'identification_number' => '1710034065',
            'name' => 'CLIENTE TEST',
            'address' => 'Quito',
        ];

        $details = [
            [
                'main_code' => 'P001',
                'auxiliary_code' => 'A001',
                'quantity' => '1.000000',
                'description' => 'Producto de prueba',
                'unit_price' => '10.000000',
                'discount' => '0.00',
                'total_without_tax' => '10.00',
            ],
        ];

        $payments = [
            [
                'method' => '01',
                'total' => '11.50',
            ],
        ];

        return [
            [
                'type' => RideDocumentType::Invoice,
                'data' => $common + [
                    'date' => '13/05/2026',
                    'sequential' => '000000025',
                    'customer' => $customer,
                    'details' => $details,
                    'payments' => $payments,
                    'total_without_taxes' => '10.00',
                    'total_discount' => '0.00',
                    'vat' => '1.50',
                    'total_amount' => '11.50',
                ],
            ],
            [
                'type' => RideDocumentType::PurchaseSettlement,
                'data' => $common + [
                    'date' => '13/05/2026',
                    'sequential' => '000000030',
                    'provider' => [
                        'identification_number' => '0102030405',
                        'name' => 'PROVEEDOR TEST',
                        'address' => 'Cuenca',
                    ],
                    'details' => $details,
                    'payments' => $payments,
                    'total_without_taxes' => '10.00',
                    'total_discount' => '0.00',
                    'vat' => '1.50',
                    'total_amount' => '11.50',
                ],
            ],
            [
                'type' => RideDocumentType::CreditNote,
                'data' => $common + [
                    'date' => '13/05/2026',
                    'sequential' => '000000026',
                    'customer' => $customer,
                    'referenced_document' => [
                        'document_type' => '01',
                        'number' => '001-001-000000025',
                        'emission_date' => '12/05/2026',
                        'reason' => 'Devolución parcial',
                    ],
                    'details' => $details,
                    'total_without_taxes' => '10.00',
                    'vat' => '1.50',
                    'modified_document_total' => '11.50',
                ],
            ],
            [
                'type' => RideDocumentType::DebitNote,
                'data' => $common + [
                    'date' => '13/05/2026',
                    'sequential' => '000000027',
                    'customer' => $customer,
                    'referenced_document' => [
                        'document_type' => '01',
                        'number' => '001-001-000000025',
                        'emission_date' => '12/05/2026',
                    ],
                    'reasons' => [
                        [
                            'reason' => 'Interés por mora',
                            'amount' => '10.00',
                        ],
                    ],
                    'payments' => $payments,
                    'total_without_taxes' => '10.00',
                    'vat' => '1.50',
                    'total_amount' => '11.50',
                ],
            ],
            [
                'type' => RideDocumentType::DeliveryGuide,
                'data' => $common + [
                    'sequential' => '000000028',
                    'carrier' => [
                        'name' => 'TRANSPORTISTA TEST',
                        'identification_number' => '1790012345001',
                        'plate' => 'PBA1234',
                    ],
                    'shipping' => [
                        'start_address' => 'Quito',
                        'start_date' => '13/05/2026',
                        'end_date' => '14/05/2026',
                    ],
                    'recipients' => [
                        [
                            'identification_number' => '1710034065',
                            'name' => 'CLIENTE TEST',
                            'destination_address' => 'Guayaquil',
                            'reason' => 'Venta',
                            'details' => $details,
                        ],
                    ],
                ],
            ],
            [
                'type' => RideDocumentType::WithholdingReceipt,
                'data' => $common + [
                    'date' => '13/05/2026',
                    'sequential' => '000000029',
                    'subject' => [
                        'name' => 'PROVEEDOR TEST S.A.',
                        'identification_number' => '1790012345001',
                        'related_party' => 'NO',
                    ],
                    'fiscal_period' => '05/2026',
                    'supporting_documents' => [
                        [
                            'support_code' => '01',
                            'document_code' => '01',
                            'document_number' => '001001000000025',
                            'emission_date' => '13/05/2026',
                            'total_without_taxes' => '100.00',
                            'total_amount' => '115.00',
                            'taxes' => [
                                ['code' => '2', 'percentage_code' => '4', 'value' => '15.00'],
                            ],
                            'withholdings' => [
                                ['code' => '1', 'withholding_code' => '303', 'value' => '10.00'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
