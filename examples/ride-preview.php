<?php

declare(strict_types=1);

use MTZ\Toolkit\RideGenerator\Config\RidePdfConfig;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\Renders\DompdfRideRenderer;
use MTZ\Toolkit\RideGenerator\Renders\TwigRideTemplateRenderer;

require dirname(__DIR__) . '/vendor/autoload.php';

$templatesPath = dirname(__DIR__) . '/src/RideGenerator/Resources/views';
$type = documentType((string) ($_GET['type'] ?? RideDocumentType::Invoice->value));
$withLogo = ($_GET['logo'] ?? '1') === '1';
$action = (string) ($_GET['action'] ?? 'index');

if ($action === 'version')
{
    noCache();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['version' => templatesVersion($templatesPath)], JSON_THROW_ON_ERROR);

    return;
}

if ($action === 'html')
{
    noCache();
    header('Content-Type: text/html; charset=utf-8');
    echo templateRenderer($templatesPath)->render(sampleRideData($type, $withLogo));

    return;
}

if ($action === 'pdf')
{
    noCache();
    $rideData = sampleRideData($type, $withLogo);
    $html = templateRenderer($templatesPath)->render($rideData);
    $pdf = (new DompdfRideRenderer())->render($html, 'preview-' . $type->value . '.pdf');

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdf->filename . '"');
    echo $pdf->content;

    return;
}

renderPreviewShell($type, $withLogo);

function templateRenderer(string $templatesPath): TwigRideTemplateRenderer
{
    return new TwigRideTemplateRenderer(
        new RidePdfConfig(
            tempDir: dirname(__DIR__) . '/var/dompdf',
            templatesPath: $templatesPath,
        ),
    );
}

function documentType(string $type): RideDocumentType
{
    try
    {
        return RideDocumentType::from($type);
    } catch (ValueError)
    {
        return RideDocumentType::Invoice;
    }
}

function noCache(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function templatesVersion(string $templatesPath): string
{
    $parts = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templatesPath));

    foreach ($files as $file)
    {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.twig'))
        {
            continue;
        }

        $parts[] = $file->getPathname() . ':' . $file->getMTime() . ':' . $file->getSize();
    }

    sort($parts);

    return sha1(implode('|', $parts));
}

function renderPreviewShell(RideDocumentType $selectedType, bool $withLogo): void
{
    $types = [
        RideDocumentType::Invoice->value => 'Factura',
        RideDocumentType::CreditNote->value => 'Nota de crédito',
        RideDocumentType::DebitNote->value => 'Nota de débito',
        RideDocumentType::DeliveryGuide->value => 'Guía de remisión',
        RideDocumentType::WithholdingReceipt->value => 'Comprobante de retención',
        RideDocumentType::PurchaseSettlement->value => 'Liquidación de compra',
    ];

    $selectedMode = (string) ($_GET['mode'] ?? 'pdf');
    $mode = in_array($selectedMode, ['html', 'pdf'], true) ? $selectedMode : 'pdf';

    noCache();
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RIDE Preview</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; color: #171717; background: #efefef; }
        .toolbar { height: 52px; background: #171717; color: #fff; padding: 9px 12px; }
        .toolbar form { margin: 0; }
        .toolbar label { font-size: 12px; margin-right: 6px; }
        .toolbar select,
        .toolbar button {
            height: 32px;
            border: 1px solid #555;
            background: #fff;
            color: #111;
            padding: 0 9px;
            margin-right: 8px;
        }
        .toolbar button.active { background: #d6e8ff; border-color: #77aee8; }
        .toolbar input { vertical-align: middle; }
        .status { float: right; font-size: 12px; line-height: 32px; color: #cfcfcf; }
        iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #777; }
    </style>
</head>
<body>
<div class="toolbar">
    <form id="controls" method="get">
        <label for="type">Documento</label>
        <select id="type" name="type">
            <?php foreach ($types as $value => $label): ?>
                <option value="<?= htmlspecialchars($value, ENT_QUOTES) ?>" <?= $selectedType->value === $value ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="hidden" id="mode" name="mode" value="<?= htmlspecialchars($mode, ENT_QUOTES) ?>">
        <button type="button" data-mode="pdf" class="<?= $mode === 'pdf' ? 'active' : '' ?>">PDF real</button>
        <button type="button" data-mode="html" class="<?= $mode === 'html' ? 'active' : '' ?>">HTML rápido</button>

        <label>
            <input type="checkbox" name="logo" value="1" <?= $withLogo ? 'checked' : '' ?>>
            Logo
        </label>

        <span class="status" id="status">Esperando cambios...</span>
    </form>
</div>

<iframe id="preview" title="RIDE preview"></iframe>

<script>
const controls = document.getElementById('controls');
const frame = document.getElementById('preview');
const statusNode = document.getElementById('status');
const modeInput = document.getElementById('mode');
let currentVersion = '';

function query(action) {
    const params = new URLSearchParams(new FormData(controls));
    params.set('action', action);
    params.set('v', Date.now().toString());
    return '?' + params.toString();
}

function reloadPreview() {
    frame.src = query(modeInput.value);
    statusNode.textContent = 'Actualizado ' + new Date().toLocaleTimeString();
}

async function poll() {
    try {
        const response = await fetch(query('version'), { cache: 'no-store' });
        const payload = await response.json();

        if (payload.version !== currentVersion) {
            currentVersion = payload.version;
            reloadPreview();
        }
    } catch (error) {
        statusNode.textContent = 'Error en preview: ' + error.message;
    }
}

controls.addEventListener('change', () => {
    currentVersion = '';
    poll();
});

document.querySelectorAll('[data-mode]').forEach((button) => {
    button.addEventListener('click', () => {
        modeInput.value = button.dataset.mode;
        document.querySelectorAll('[data-mode]').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
        currentVersion = '';
        poll();
    });
});

poll();
setInterval(poll, 800);
</script>
</body>
</html>
<?php
}

function sampleRideData(RideDocumentType $type, bool $withLogo): RideData
{
    $company = [
        'ruc' => '1790012345001',
        'legal_name' => 'MTZ TEST S.A.',
        'trade_name' => 'MTZ TEST',
        'head_office_address' => 'Quito, Av. Amazonas N34-120',
        'establishment_address' => 'Quito, Av. Amazonas N34-120',
        'special_taxpayer' => '123',
        'requires_accounting' => 'SI',
    ];

    if ($withLogo)
    {
        $company['logo_base64'] = sampleLogo();
    }

    $common = [
        'company' => $company,
        'establishment_code' => '001',
        'emission_point_code' => '001',
        'sequential' => sequential($type),
        'date' => '13/05/2026',
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
                'Serie' => 'S-001',
            ],
            'unit_price' => '10.000000',
            'discount' => '0.00',
            'total_without_tax' => '10.00',
        ],
    ];

    $payments = [
        [
            'method' => '01',
            'method_label' => 'SIN UTILIZACIÓN DEL SISTEMA FINANCIERO',
            'total' => '11.50',
        ],
    ];

    $data = match ($type)
    {
        RideDocumentType::Invoice => $common + [
            'customer' => $customer,
            'delivery_guide' => '001-001-000000001',
            'details' => $details,
            'payments' => $payments,
            'subtotal_15' => '10.00',
            'subtotal_0' => '0.00',
            'total_without_taxes' => '10.00',
            'total_discount' => '0.00',
            'vat' => '1.50',
            'tip' => '0.00',
            'total_amount' => '11.50',
        ],
        RideDocumentType::PurchaseSettlement => $common + [
            'customer' => [
                'identification_number' => '0102030405',
                'name' => 'PROVEEDOR TEST',
                'address' => 'Cuenca',
            ],
            'details' => $details,
            'payments' => $payments,
            'subtotal_15' => '10.00',
            'total_without_taxes' => '10.00',
            'total_discount' => '0.00',
            'vat' => '1.50',
            'total_amount' => '11.50',
        ],
        RideDocumentType::CreditNote => $common + [
            'customer' => $customer,
            'referenced_document' => [
                'number' => '001-001-000000025',
                'emission_date' => '12/05/2026',
                'reason' => 'Devolución parcial',
            ],
            'details' => $details,
            'total_without_taxes' => '10.00',
            'vat' => '1.50',
            'modified_document_total' => '11.50',
        ],
        RideDocumentType::DebitNote => $common + [
            'customer' => $customer,
            'referenced_document' => [
                'number' => '001-001-000000025',
                'emission_date' => '12/05/2026',
            ],
            'reasons' => [
                ['reason' => 'Interés por mora', 'amount' => '10.00'],
            ],
            'payments' => $payments,
            'total_without_taxes' => '10.00',
            'vat' => '1.50',
            'total_amount' => '11.50',
        ],
        RideDocumentType::DeliveryGuide => $common + [
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
                    'supporting_document_number' => '001-001-000000025',
                    'route' => 'Quito - Guayaquil',
                    'details' => $details,
                ],
            ],
        ],
        RideDocumentType::WithholdingReceipt => $common + [
            'subject' => [
                'name' => 'PROVEEDOR TEST S.A.',
                'identification_number' => '1790012345001',
                'related_party' => 'NO',
            ],
            'fiscal_period' => '05/2026',
            'supporting_documents' => [
                [
                    'document_number' => '001001000000025',
                    'emission_date' => '13/05/2026',
                    'total_without_taxes' => '100.00',
                    'total_amount' => '115.00',
                    'withholdings' => [
                        [
                            'code' => '1',
                            'withholding_code' => '303',
                            'tax_base' => '100.00',
                            'percentage' => '10',
                            'value' => '10.00',
                        ],
                    ],
                ],
            ],
        ],
    };

    return RideData::make(
        documentType: $type,
        accessKey: accessKey($type),
        data: $data,
        authorizationNumber: accessKey($type),
        authorizationDate: '13/05/2026 10:30:00',
    );
}

function sequential(RideDocumentType $type): string
{
    return match ($type)
    {
        RideDocumentType::Invoice => '000000025',
        RideDocumentType::CreditNote => '000000026',
        RideDocumentType::DebitNote => '000000027',
        RideDocumentType::DeliveryGuide => '000000028',
        RideDocumentType::WithholdingReceipt => '000000029',
        RideDocumentType::PurchaseSettlement => '000000030',
    };
}

function accessKey(RideDocumentType $type): string
{
    return '130520260117900123450011001001' . sequential($type) . '1234567817';
}

function sampleLogo(): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="340" height="130" viewBox="0 0 340 130"><rect width="340" height="130" fill="#111"/><text x="170" y="57" fill="#fff" font-family="Arial" font-size="30" font-weight="700" text-anchor="middle">MTZ TEST</text><text x="170" y="88" fill="#d6e8ff" font-family="Arial" font-size="16" text-anchor="middle">Electronic Documents</text></svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
