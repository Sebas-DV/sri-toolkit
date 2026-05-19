# Catalogos

`CatalogRegistry` centraliza codigos frecuentes para payloads XML sin dejar valores sueltos quemados en la aplicacion. Los valores por defecto vienen de la ficha tecnica oficial offline v2.26 y del anexo ICE.

## Uso

```php
use MTZ\Toolkit\Catalogs\Catalogs;

$catalogRegistry = Catalogs::registry();

$vat = $catalogRegistry->get('vat-rates', '4');
$paymentMethods = $catalogRegistry->list('payment-methods');
$vatWithholding = $catalogRegistry->list('vat-withholding');
$metadata = $catalogRegistry->getMeta('vat-rates');
```

PHP reserva `list` como nombre de metodo declarado. Por eso el metodo formal es `entries()`, pero `$catalogRegistry->list(...)` funciona mediante `__call()` para mantener compatibilidad con la forma de API del paquete TypeScript de referencia.

```php
$paymentMethods = $catalogRegistry->entries('payment-methods');
```

## Catalogos Disponibles

| Catalogo | Uso principal |
| --- | --- |
| `identification-types` | Identificacion de comprador, sujeto retenido, proveedor y transportista |
| `document-types` | `codDoc`, documentos sustento y claves de acceso |
| `payment-methods` | `formaPago` dentro de `pagos` |
| `vat-rates` | `codigoPorcentaje` cuando el impuesto `codigo` es `2` |
| `vat-withholding` | `codigoRetencion` cuando se retiene IVA |
| `tax-codes` | Valores `codigo` de impuestos y retenciones |
| `support-codes` | `codSustento` en comprobantes de retencion |
| `ice-rates` | Valores ICE para `codigoPorcentaje`; la tarifa se calcula segun la normativa vigente |

## Codigos Frecuentes

### Tipos De Identificacion

| Codigo | Tipo |
| --- | --- |
| `04` | RUC |
| `05` | Cedula |
| `06` | Pasaporte |
| `07` | Consumidor final |
| `08` | Identificacion del exterior |

### IVA

| Codigo | Tarifa | Notas |
| --- | --- | --- |
| `0` | 0% | IVA 0% |
| `2` | 12% | Historico |
| `3` | 14% | Historico |
| `4` | 15% | Tarifa comun vigente |
| `5` | 5% | Agregado por la ficha tecnica v2.26 |
| `6` | 0% | No objeto de impuesto |
| `7` | 0% | Exento de IVA |
| `8` | variable | IVA diferenciado |
| `10` | 13% | Agregado por la ficha tecnica v2.26 |

### Formas De Pago

| Codigo | Descripcion |
| --- | --- |
| `01` | Sin utilizacion del sistema financiero |
| `15` | Compensacion de deudas |
| `16` | Tarjeta de debito |
| `17` | Dinero electronico |
| `18` | Tarjeta prepago |
| `19` | Tarjeta de credito |
| `20` | Otros con utilizacion del sistema financiero |
| `21` | Endoso de titulos |

### Retencion IVA

| Codigo | Porcentaje |
| --- | --- |
| `9` | 10% |
| `10` | 20% |
| `1` | 30% |
| `11` | 50% |
| `2` | 70% |
| `3` | 100% |
| `7` | 0%, retencion en cero |
| `8` | 0%, no procede retencion |

Catalogo completo: usa `$catalogRegistry->list('vat-withholding')`.

## Sobreescritura En Ejecucion

Cuando cambie una tarifa o codigo, puedes sobreescribir un catalogo sin esperar una nueva version:

```php
$catalogRegistry->override('vat-rates', [
    '4' => ['code' => '4', 'description' => 'VAT 16%', 'rate' => 16],
], [
    'source' => 'Internal resolution',
    'updatedAt' => '2026-06-01',
]);

$catalogRegistry->reset('vat-rates');
$catalogRegistry->resetAll();
```

## Documentos Soportados

La ficha oficial lista seis tipos de comprobantes electronicos:

| Codigo | Documento | Clave de acceso | XMLMaker |
| --- | --- | --- | --- |
| `01` | Factura | Si | Si |
| `03` | Liquidacion de compra | Si | Si |
| `04` | Nota de credito | Si | Si |
| `05` | Nota de debito | Si | Si |
| `06` | Guia de remision | Si | Si |
| `07` | Comprobante de retencion | Si | Si |
