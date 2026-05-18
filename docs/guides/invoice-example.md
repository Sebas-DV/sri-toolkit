# Ejemplo de factura

Este es un payload completo para generar una factura de prueba.

```php
$invoicePayload = [
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
];
```

## Generacion

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
```

## Validar rapidamente

```php
$document = new DOMDocument();

if (! $document->loadXML($xml)) {
    throw new RuntimeException('XML invalido.');
}
```

## Campos adicionales

`additional_info` se transforma en `infoAdicional`:

```xml
<infoAdicional>
  <campoAdicional nombre="Email">cliente@example.com</campoAdicional>
</infoAdicional>
```
