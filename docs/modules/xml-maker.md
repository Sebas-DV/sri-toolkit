# XMLMaker

`XMLMaker` convierte un payload PHP en un `DOMDocument` compatible con la estructura XML del SRI.

Para ejemplos completos de cada comprobante soportado, revisa [Generar XML por tipo](/guides/xml-by-document-type).

## Estado de soporte

| Documento | Estado |
| --- | --- |
| Factura (`XmlDocumentType::Invoice`) | Implementado |
| Liquidacion de compra (`XmlDocumentType::PurchaseSettlement`) | Implementado |
| Nota de credito (`XmlDocumentType::CreditNote`) | Implementado |
| Nota de debito (`XmlDocumentType::DebitNote`) | Implementado |
| Guia de remision (`XmlDocumentType::DeliveryGuide`) | Implementado |
| Comprobante de retencion (`XmlDocumentType::WithholdingReceipt`) | Implementado |

## API principal

```php
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
use MTZ\Toolkit\XMLMaker\XMLMaker;

$generatedXml = (new XMLMaker())->generate(
    XmlGenerationData::make(
        documentType: XmlDocumentType::Invoice,
        environment: XmlEnvironment::Testing,
        accessKey: $accessKey,
        data: $invoicePayload,
    ),
);

$xml = $generatedXml->toString();
$document = $generatedXml->document;
```

## Resultado

`GeneratedXml` expone:

| Propiedad | Tipo | Descripcion |
| --- | --- | --- |
| `documentType` | `XmlDocumentType` | Tipo generado |
| `accessKey` | `string` | Clave de acceso usada |
| `document` | `DOMDocument` | Documento XML |
| `toString()` | `string` | XML serializado |

## Payload base de factura

```php
$invoicePayload = [
    'date' => '13/05/2026',
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
            'term' => null,
            'time_unit' => null,
        ],
    ],
    'details' => [
        [
            'main_code' => 'P001',
            'auxiliary_code' => null,
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
];
```

## Campos obligatorios de factura

| Campo | Descripcion |
| --- | --- |
| `date` | Fecha de emision en formato SRI, por ejemplo `13/05/2026` |
| `sequential` | Secuencial de 9 digitos |
| `company.ruc` | RUC emisor |
| `company.legal_name` | Razon social |
| `company.head_office_address` | Direccion matriz |
| `establishment.code` | Codigo de establecimiento |
| `emission_point.code` | Punto de emision |
| `customer.identification_type` | Tipo de identificacion del comprador |
| `customer.identification_number` | Identificacion del comprador |
| `customer.name` | Razon social del comprador |
| `establishment_address` | Direccion del establecimiento |
| `total_without_taxes` | Total sin impuestos |
| `total_discount` | Total descuento |
| `tax_totals` | Totales de impuestos, no puede estar vacio |
| `total_amount` | Importe total |
| `details` | Detalles, no puede estar vacio |
| `details.*.taxes` | Impuestos por detalle, no puede estar vacio |

## Campos opcionales

`trade_name`, `withholding_agent`, `rimpe_regime_taxpayer`, `requires_accounting`, `customer.address`, `tip`, `currency`, `payments`, `payments.*.term`, `payments.*.time_unit`, `details.*.auxiliary_code`, `details.*.discount` y `additional_info`.

Si `currency` esta vacio, el builder usa `DOLAR`.

## XML generado

El nodo raiz se crea con `id="comprobante"` y la version del tipo documental:

```xml
<factura id="comprobante" version="2.1.0">
```

Ese atributo es necesario para la firma posterior.
