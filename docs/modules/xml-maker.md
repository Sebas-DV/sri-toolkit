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

## Calculo automatico de totales

Por defecto, `XMLMaker` deriva `total_without_taxes`, `total_amount` y `tax_totals` desde las lineas de detalle (factura, liquidacion de compra y nota de credito), usando aritmetica decimal exacta. Esto mantiene el XML cuadrado y evita el error 52 (diferencias en calculos). Los valores calculados sobrescriben los provistos en el payload.

Para enviar los totales manualmente, desactivalo:

```php
use MTZ\Toolkit\XMLMaker\Config\XmlMakerConfig;
use MTZ\Toolkit\XMLMaker\XMLMaker;

$maker = new XMLMaker(new XmlMakerConfig(calculateTotals: false));
```

Ver [Validacion](/modules/validation) para el detalle de `TotalsCalculator`.

## Validacion contra XSD

Antes de firmar, valida el XML contra el esquema oficial incluido:

```php
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;

$errors = (new XsdValidator())->validate($xml, XmlDocumentType::Invoice);
```

Los seis tipos generan XML conforme al XSD oficial del SRI.

## Variantes de factura

La factura admite bloques opcionales que se emiten solo cuando envias su clave en el payload:

| Variante | Clave del payload |
| --- | --- |
| Exportacion (comercio exterior, incoterms, fletes) | `export` |
| Reembolso (detalle de reembolsos) | `reimbursements` |
| Sustitutiva de guia de remision | `substitute_delivery_guide` |
| Rubros de terceros | `third_party_items` |
| Maquina fiscal | `fiscal_machine` |
| Placa (combustibles) | `plate` |

Ejemplo de exportacion:

```php
$invoicePayload['export'] = [
    'foreign_trade' => 'EXPORTADOR',
    'incoterm' => 'FOB',
    'incoterm_place' => 'GUAYAQUIL',
    'origin_country' => '593',
    'destination_country' => '840',
    'international_freight' => '0.00',
    'international_insurance' => '0.00',
    'customs_expenses' => '0.00',
    'other_transport_expenses' => '0.00',
];
```

## Devolucion de IVA

Para la devolucion de IVA (ANEXO 20), agrega `refund_value` en el impuesto de la linea. Se agrega y se emite como `valorDevolucionIva` en `totalConImpuestos`. Solo aplica con comprador de tipo cedula (05) y el monto debe coincidir con el autorizado por el servicio DIG.

```php
'taxes' => [
    ['code' => '2', 'percentage_code' => '0', 'rate' => '12.00', 'taxable_base' => '50.00', 'value' => '6.00', 'refund_value' => '6.00'],
],
```

## XML generado

El nodo raiz se crea con `id="comprobante"` y la version del tipo documental:

```xml
<factura id="comprobante" version="2.1.0">
```

Ese atributo es necesario para la firma posterior.
