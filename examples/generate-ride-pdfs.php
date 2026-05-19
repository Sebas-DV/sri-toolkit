<?php

declare(strict_types=1);

use MTZ\Toolkit\AccessKeyGenerator\AccessKeyGenerator;
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\RideGenerator;

require dirname(__DIR__) . '/vendor/autoload.php';

$outputDirectory = dirname(__DIR__) . '/var/ride-samples';

if (! is_dir($outputDirectory))
{
    mkdir($outputDirectory, 0775, true);
}

$openFiles = ! in_array('--no-open', $argv, true);
$generator = new RideGenerator();
$accessKeyGenerator = new AccessKeyGenerator();

$company = [
    'ruc' => '1790012345001',
    'legal_name' => 'MTZ TEST S.A.',
    'trade_name' => 'MTZ TEST',
    'head_office_address' => 'Quito, Av. Amazonas N34-120',
    'special_taxpayer' => '123',
    'requires_accounting' => 'SI',
];

$common = [
    'company' => $company,
    'establishment_code' => '001',
    'emission_point_code' => '001',
    'establishment_address' => 'Quito, Av. Amazonas N34-120',
    'environment_label' => 'PRUEBAS',
    'emission_label' => 'NORMAL',
    'requires_accounting' => 'SI',
    'additional_info' => [
        'Email' => 'cliente@example.com',
        'Teléfono' => '0999999999',
    ],
];

$customer = [
    'identification_type' => '05',
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
        'additional_info' => [
            'Color' => 'Azul',
            'Lote' => 'A1',
        ],
        'unit_price' => '10.000000',
        'discount' => '0.00',
        'total_without_tax' => '10.00',
    ],
];

$payments = [
    [
        'method' => '01',
        'method_label' => 'Sin utilización del sistema financiero',
        'total' => '11.50',
    ],
];

$documents = [
    [
        'type' => RideDocumentType::Invoice,
        'access_type' => DocumentType::Invoice,
        'sequential' => 25,
        'data' => $common + [
            'date' => '13/05/2026',
            'sequential' => '000000025',
            'customer' => $customer,
            'delivery_guide' => '001-001-000000001',
            'details' => $details,
            'payments' => $payments,
            'subtotal_12' => '10.00',
            'subtotal_0' => '0.00',
            'total_without_taxes' => '10.00',
            'total_discount' => '0.00',
            'vat' => '1.50',
            'tip' => '0.00',
            'total_amount' => '11.50',
            'total_without_subsidy' => '11.50',
            'subsidy_saving' => '0.00',
        ],
    ],
    [
        'type' => RideDocumentType::PurchaseSettlement,
        'access_type' => DocumentType::PurchaseSettlement,
        'sequential' => 30,
        'data' => $common + [
            'date' => '13/05/2026',
            'sequential' => '000000030',
            'provider' => [
                'identification_type' => '05',
                'identification_number' => '0102030405',
                'name' => 'PROVEEDOR TEST',
                'address' => 'Cuenca',
            ],
            'details' => $details,
            'payments' => $payments,
            'subtotal_12' => '10.00',
            'total_without_taxes' => '10.00',
            'total_discount' => '0.00',
            'vat' => '1.50',
            'total_amount' => '11.50',
        ],
    ],
    [
        'type' => RideDocumentType::CreditNote,
        'access_type' => DocumentType::CreditNote,
        'sequential' => 26,
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
            'currency' => 'DOLAR',
        ],
    ],
    [
        'type' => RideDocumentType::DebitNote,
        'access_type' => DocumentType::DebitNote,
        'sequential' => 27,
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
        'access_type' => DocumentType::RemissionGuide,
        'sequential' => 28,
        'data' => $common + [
            'sequential' => '000000028',
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
            'recipients' => [
                [
                    'identification_number' => '1710034065',
                    'name' => 'CLIENTE TEST',
                    'destination_address' => 'Guayaquil',
                    'reason' => 'Venta',
                    'supporting_document_code' => '01',
                    'supporting_document_number' => '001-001-000000025',
                    'supporting_document_authorization' => '1305202601179001234500110010010000000251234567817',
                    'route' => 'Quito - Guayaquil',
                    'details' => [
                        [
                            'main_code' => 'P001',
                            'auxiliary_code' => 'A001',
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
    ],
    [
        'type' => RideDocumentType::WithholdingReceipt,
        'access_type' => DocumentType::RetentionVoucher,
        'sequential' => 29,
        'data' => $common + [
            'date' => '13/05/2026',
            'sequential' => '000000029',
            'subject' => [
                'identification_type' => '04',
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
                        [
                            'code' => '2',
                            'percentage_code' => '4',
                            'value' => '15.00',
                        ],
                    ],
                    'withholdings' => [
                        [
                            'code' => '1',
                            'withholding_code' => '303',
                            'value' => '10.00',
                        ],
                    ],
                ],
            ],
        ],
    ],
];

foreach ($documents as $document)
{
    $accessKey = $accessKeyGenerator->generate(
        AccessKeyData::make(
            emissionDate: '2026-05-13',
            documentType: $document['access_type'],
            ruc: $company['ruc'],
            environment: Environment::Testing,
            sequential: $document['sequential'],
            numericCode: '12345678',
            establishmentCode: '001',
            emissionPointCode: '001',
        ),
    );

    $pdf = $generator->generate(
        RideData::make(
            documentType: $document['type'],
            accessKey: $accessKey,
            data: $document['data'],
            authorizationNumber: $accessKey,
            authorizationDate: '13/05/2026 10:30:00',
        ),
    );

    $path = $outputDirectory . '/' . $pdf->filename;
    $pdf->saveTo($path);

    echo $path . PHP_EOL;

    if ($openFiles)
    {
        openFile($path);
    }
}

function openFile(string $path): void
{
    if (PHP_OS_FAMILY === 'Windows')
    {
        $escapedPath = str_replace("'", "''", $path);
        pclose(popen('powershell -NoProfile -Command "Start-Process -LiteralPath \'' . $escapedPath . '\'"', 'r'));

        return;
    }

    if (PHP_OS_FAMILY === 'Darwin')
    {
        exec('open ' . escapeshellarg($path) . ' > /dev/null 2>&1 &');

        return;
    }

    exec('xdg-open ' . escapeshellarg($path) . ' > /dev/null 2>&1 &');
}
