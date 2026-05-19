# Generar XML por tipo

Esta guia documenta como generar cada comprobante electronico soportado por `XMLMaker`.

El flujo siempre tiene tres entradas:

- `documentType`: el valor de `XmlDocumentType` que define raiz, codigo y version XML.
- `accessKey`: clave de acceso de 49 digitos generada para el mismo tipo documental.
- `data`: payload PHP con los campos del comprobante.

## Funcion base

Usa una funcion de aplicacion como punto unico para serializar y persistir el XML.

```php
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
use MTZ\Toolkit\XMLMaker\XMLMaker;

function buildSriXml(
    XmlDocumentType $documentType,
    string $accessKey,
    array $payload,
): string {
    return (new XMLMaker())->generate(
        XmlGenerationData::make(
            documentType: $documentType,
            environment: XmlEnvironment::Testing,
            accessKey: $accessKey,
            data: $payload,
        ),
    )->toString();
}
```

Para guardar el archivo:

```php
$xml = buildSriXml(XmlDocumentType::Invoice, $accessKey, $invoicePayload);

file_put_contents(__DIR__ . "/storage/sri/{$accessKey}.xml", $xml);
```

## Tipos soportados

| Comprobante | Enum | Codigo | Raiz | Version |
| --- | --- | --- | --- | --- |
| Factura | `XmlDocumentType::Invoice` | `01` | `factura` | `2.1.0` |
| Liquidacion de compra | `XmlDocumentType::PurchaseSettlement` | `03` | `liquidacionCompra` | `1.1.0` |
| Nota de credito | `XmlDocumentType::CreditNote` | `04` | `notaCredito` | `1.1.0` |
| Nota de debito | `XmlDocumentType::DebitNote` | `05` | `notaDebito` | `1.0.0` |
| Guia de remision | `XmlDocumentType::DeliveryGuide` | `06` | `guiaRemision` | `1.1.0` |
| Comprobante de retencion | `XmlDocumentType::WithholdingReceipt` | `07` | `comprobanteRetencion` | `2.0.0` |

## Clave de acceso por tipo

La clave de acceso y el XML deben usar el mismo codigo de comprobante. Si generas una clave `01` y luego construyes una guia de remision `06`, el SRI rechazara el archivo aunque el XML este bien formado.

| XML generado | Tipo para la clave de acceso |
| --- | --- |
| `XmlDocumentType::Invoice` | `DocumentType::Invoice` |
| `XmlDocumentType::PurchaseSettlement` | `DocumentType::PurchaseSettlement` |
| `XmlDocumentType::CreditNote` | `DocumentType::CreditNote` |
| `XmlDocumentType::DebitNote` | `DocumentType::DebitNote` |
| `XmlDocumentType::DeliveryGuide` | `DocumentType::RemissionGuide` |
| `XmlDocumentType::WithholdingReceipt` | `DocumentType::RetentionVoucher` |

Ejemplo para factura:

```php
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment;
use MTZ\Toolkit\AccessKeyGenerator\AccessKeyGenerator;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;

$accessKey = (new AccessKeyGenerator())->generate(
    AccessKeyData::make(
        emissionDate: '2026-05-13',
        documentType: DocumentType::Invoice,
        ruc: '1790012345001',
        environment: Environment::Testing,
        sequential: '000000025',
        numericCode: '12345678',
        establishmentCode: '001',
        emissionPointCode: '001',
    ),
);

$xml = buildSriXml(XmlDocumentType::Invoice, $accessKey, $invoicePayload);
```

Para otros comprobantes cambia `DocumentType::*`, `XmlDocumentType::*`, `sequential` y el payload del comprobante. El tipo, la fecha, RUC, ambiente, establecimiento, punto de emision y secuencial deben coincidir entre la clave y el contenido del XML. En `AccessKeyData::make()` puedes usar una fecha parseable por PHP como `2026-05-13`; en el payload XML del SRI se emite como `13/05/2026`.

## Campos comunes

Todos los payloads usan `infoTributaria` desde estos campos:

```php
[
    'sequential' => '000000025',
    'company' => [
        'ruc' => '1790012345001',
        'legal_name' => 'MTZ TEST S.A.',
        'trade_name' => 'MTZ TEST',
        'head_office_address' => 'Quito',
        'withholding_agent' => null,
        'rimpe_regime_taxpayer' => null,
    ],
    'establishment' => [
        'code' => '001',
    ],
    'emission_point' => [
        'code' => '001',
    ],
    'additional_info' => [
        'Email' => 'cliente@example.com',
    ],
]
```

`company.trade_name`, `company.withholding_agent`, `company.rimpe_regime_taxpayer` y `additional_info` son opcionales.

## Factura

Genera una factura con `XmlDocumentType::Invoice`.

```php
$invoicePayload = [
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
    'tax_totals' => [
        [
            'code' => '2',
            'percentage_code' => '4',
            'taxable_base' => '10.00',
            'value' => '1.50',
            'additional_discount' => '0.00',
            'refund_value' => '0.00',
        ],
    ],
    'tip' => '0.00',
    'total_amount' => '11.50',
    'currency' => 'DOLAR',
    'payments' => [
        ['method' => '01', 'total' => '11.50'],
    ],
    'details' => [
        [
            'main_code' => 'P001',
            'auxiliary_code' => 'A001',
            'description' => 'Producto de prueba',
            'unit' => 'UND',
            'quantity' => '1.00',
            'unit_price' => '10.00',
            'unit_price_without_subsidy' => '12.00',
            'discount' => '0.00',
            'total_without_tax' => '10.00',
            'additional_info' => [
                'Color' => 'Azul',
            ],
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
    'additional_info' => [
        'Email' => 'cliente@example.com',
    ],
];

$xml = buildSriXml(XmlDocumentType::Invoice, $accessKey, $invoicePayload);
```

Campos obligatorios: `date`, `sequential`, `company`, `establishment`, `emission_point`, `customer`, `establishment_address`, `total_without_taxes`, `total_discount`, `tax_totals`, `total_amount`, `details` y `details.*.taxes`.

## Liquidacion de compra

Genera una liquidacion de compra con `XmlDocumentType::PurchaseSettlement`. El proveedor se declara en `provider`.

```php
$purchaseSettlementPayload = [
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
        ['method' => '01', 'total' => '11.50'],
    ],
    'details' => [
        [
            'main_code' => 'P001',
            'auxiliary_code' => 'A001',
            'description' => 'Purchased service',
            'unit' => 'UND',
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
        'Email' => 'provider@example.com',
    ],
];

$xml = buildSriXml(XmlDocumentType::PurchaseSettlement, $accessKey, $purchaseSettlementPayload);
```

Campos obligatorios: `date`, `sequential`, `company`, `establishment`, `emission_point`, `provider`, `establishment_address`, `total_without_taxes`, `total_discount`, `tax_totals`, `total_amount`, `details` y `details.*.taxes`.

## Nota de credito

Genera una nota de credito con `XmlDocumentType::CreditNote`. El documento referenciado se declara en `referenced_document`.

```php
$creditNotePayload = [
    'date' => '13/05/2026',
    'sequential' => '000000026',
    'company' => [
        'ruc' => '1790012345001',
        'legal_name' => 'MTZ TEST S.A.',
        'head_office_address' => 'Quito',
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
        'number' => '001-001-000000025',
        'emission_date' => '12/05/2026',
        'reason' => 'Devolucion parcial',
    ],
    'establishment_address' => 'Quito',
    'requires_accounting' => 'NO',
    'total_without_taxes' => '10.00',
    'modified_document_total' => '11.50',
    'currency' => 'DOLAR',
    'tax_totals' => [
        [
            'code' => '2',
            'percentage_code' => '4',
            'taxable_base' => '10.00',
            'value' => '1.50',
        ],
    ],
    'details' => [
        [
            'main_code' => 'P001',
            'description' => 'Producto devuelto',
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

$xml = buildSriXml(XmlDocumentType::CreditNote, $accessKey, $creditNotePayload);
```

Campos obligatorios: `referenced_document.document_type`, `referenced_document.number`, `referenced_document.emission_date`, `referenced_document.reason`, `modified_document_total`, `tax_totals`, `details` y `details.*.taxes`.

## Nota de debito

Genera una nota de debito con `XmlDocumentType::DebitNote`. El total del comprobante se emite como `valorTotal`.

```php
$debitNotePayload = [
    'date' => '13/05/2026',
    'sequential' => '000000027',
    'company' => [
        'ruc' => '1790012345001',
        'legal_name' => 'MTZ TEST S.A.',
        'head_office_address' => 'Quito',
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
        'number' => '001-001-000000025',
        'emission_date' => '12/05/2026',
    ],
    'establishment_address' => 'Quito',
    'requires_accounting' => 'NO',
    'total_without_taxes' => '10.00',
    'tax_totals' => [
        [
            'code' => '2',
            'percentage_code' => '4',
            'rate' => '15.00',
            'taxable_base' => '10.00',
            'value' => '1.50',
        ],
    ],
    'total_amount' => '11.50',
    'payments' => [
        ['method' => '01', 'total' => '11.50'],
    ],
    'reasons' => [
        [
            'reason' => 'Interes por mora',
            'amount' => '10.00',
        ],
    ],
];

$xml = buildSriXml(XmlDocumentType::DebitNote, $accessKey, $debitNotePayload);
```

Campos obligatorios: `referenced_document`, `total_without_taxes`, `tax_totals`, `total_amount` y `reasons`.

## Guia de remision

Genera una guia de remision con `XmlDocumentType::DeliveryGuide`.

```php
$deliveryGuidePayload = [
    'sequential' => '000000028',
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
                    'additional_info' => [
                        'Lote' => 'A1',
                    ],
                ],
            ],
        ],
    ],
];

$xml = buildSriXml(XmlDocumentType::DeliveryGuide, $accessKey, $deliveryGuidePayload);
```

Campos obligatorios: `carrier`, `shipping.start_address`, `shipping.start_date`, `shipping.end_date`, `carrier.plate`, `recipients` y `recipients.*.details`.

## Comprobante de retencion

Genera un comprobante de retencion version `2.0.0` con `XmlDocumentType::WithholdingReceipt`.

```php
$withholdingPayload = [
    'date' => '13/05/2026',
    'sequential' => '000000029',
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
                ],
            ],
            'payments' => [
                ['method' => '01', 'total' => '115.00'],
            ],
        ],
    ],
];

$xml = buildSriXml(XmlDocumentType::WithholdingReceipt, $accessKey, $withholdingPayload);
```

Campos obligatorios: `subject.related_party`, `fiscal_period`, `supporting_documents`, `supporting_documents.*.taxes`, `supporting_documents.*.withholdings` y `supporting_documents.*.payments`.

## Firmar despues de generar

El XML generado por cualquiera de estos tipos queda listo para `Signer` porque incluye `id="comprobante"` en el nodo raiz.

```php
use MTZ\Toolkit\Signer\Signer;

$signedXml = (new Signer(
    certificatePath: '/secure/path/certificate.p12',
    certificatePassword: getenv('SRI_CERTIFICATE_PASSWORD') ?: '',
))
    ->loadXml($xml)
    ->sign();
```

## Errores de payload

Si falta un campo obligatorio o un arreglo requerido esta vacio, `XMLMaker` lanza una excepcion de generacion XML. Trata estos errores como validaciones de integracion: el payload debe estar completo antes de firmar o enviar al SRI.
