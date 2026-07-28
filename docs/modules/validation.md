# Validacion

El toolkit incluye tres validaciones que ayudan a evitar rechazos del SRI antes de enviar: esquema XSD, identificacion y calculo de totales.

## Validacion XSD

`XsdValidator` valida el XML generado contra el XSD oficial del SRI incluido en el paquete (`src/XMLMaker/Resources/schemas`). Atrapa offline los errores 35 (documento invalido), 36 y 48.

```php
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;

$validator = new XsdValidator();

$errors = $validator->validate($xml, XmlDocumentType::Invoice); // list<string>

if ($validator->isValid($xml, XmlDocumentType::Invoice)) {
    // conforme al esquema oficial
}
```

`validate()` devuelve la lista de errores (vacia si es valido). `XsdValidator` implementa `SchemaValidatorInterface`, util para inyectarlo o reemplazarlo en el [pipeline](/modules/pipeline).

El esquema se resuelve por tipo y version con `SchemaLocator`, usando la version objetivo de cada comprobante.

## Validacion de identificacion

`IdentificationValidator` valida cedula (modulo 10) y RUC (modulo 11), evitando las advertencias 59 y 62.

```php
use MTZ\Toolkit\XMLMaker\Validation\IdentificationValidator;

$validator = new IdentificationValidator();

$validator->isValidCedula('1710034065');       // true
$validator->isValidRuc('1760013210001');       // true

// Despacha por tipo de identificacion SRI (04 RUC, 05 cedula, 06 pasaporte, 07 consumidor final, 08 exterior)
$validator->isValid('1710034065', IdentificationValidator::TYPE_CEDULA);
$validator->isValid('9999999999999', IdentificationValidator::TYPE_FINAL_CONSUMER); // true
```

Pasaporte e identificacion del exterior no aplican algoritmo (solo se exige no vacio); consumidor final siempre es valido. Sin tipo, se deduce por longitud (10 = cedula, 13 = RUC).

## Calculo de totales

`TotalsCalculator` deriva los totales del comprobante desde las lineas de detalle usando aritmetica decimal exacta (brick/money), garantizando que el XML cuadre y evitando el error 52 (diferencias en calculos).

```php
use MTZ\Toolkit\XMLMaker\Calculation\TotalsCalculator;

$totals = (new TotalsCalculator())->calculate($details, tip: 0.0);

$totals->totalWithoutTaxes;  // "330.00"
$totals->importeTotal;       // "369.50"
$totals->ice;
$totals->irbpnr;
$totals->taxTotals;          // list<TaxTotal> agrupados por (codigo, tarifa)
$totals->toArray();          // claves listas para el payload de XMLMaker
```

Cada linea de detalle aporta `total_without_tax`, `discount` y `taxes` (con `code`, `percentage_code`, `rate`, `taxable_base`, `value` y `refund_value` opcional para devolucion de IVA). Los impuestos se agrupan por `(codigo, tarifa)` y se preserva `valorDevolucionIva`.

`XMLMaker` usa este calculador por defecto para factura, liquidacion de compra y nota de credito. Se desactiva con `calculateTotals: false` en `XmlMakerConfig`:

```php
use MTZ\Toolkit\XMLMaker\Config\XmlMakerConfig;
use MTZ\Toolkit\XMLMaker\XMLMaker;

$maker = new XMLMaker(new XmlMakerConfig(calculateTotals: false));
```
