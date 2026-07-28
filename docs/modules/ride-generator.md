# RideGenerator

`RideGenerator` genera el RIDE (Representacion Impresa de Documento Electronico) en PDF para los seis comprobantes, a partir de plantillas Twig renderizadas con Dompdf.

## Uso basico

```php
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\RideGenerator;

$pdf = (new RideGenerator())->generate(
    RideData::make(
        documentType: RideDocumentType::Invoice,
        accessKey: $accessKey,
        data: $ridePayload,
        authorizationNumber: $accessKey,
        authorizationDate: '17/03/2024 14:33:35',
    ),
);

$pdf->content;              // bytes del PDF (%PDF-...)
$pdf->filename;             // RIDE-INVOICE-<claveAcceso>.pdf
$pdf->saveTo('/ruta/ride.pdf');
```

`generate()` devuelve un `GeneratedRidePdf` con `content` (bytes), `filename`, `saveTo(string $path)` y `toString()`.

## Tipos de documento

```php
RideDocumentType::Invoice;             // Factura
RideDocumentType::CreditNote;          // Nota de credito
RideDocumentType::DebitNote;           // Nota de debito
RideDocumentType::WithholdingReceipt;  // Comprobante de retencion
RideDocumentType::DeliveryGuide;       // Guia de remision
RideDocumentType::PurchaseSettlement;  // Liquidacion de compra
```

## RideData

| Campo | Requerido | Descripcion |
| --- | --- | --- |
| `documentType` | Si | Tipo de RIDE a generar |
| `accessKey` | Si | Clave de acceso de 49 digitos (tambien alimenta el codigo de barras) |
| `data` | Si | Payload del comprobante (misma forma que el usado en XMLMaker mas campos de presentacion) |
| `authorizationNumber` | No | Numero de autorizacion; por defecto la clave de acceso |
| `authorizationDate` | No | Fecha y hora de autorizacion |

El `data` reutiliza las claves del payload de XMLMaker (company, customer, details, payments, etc.) y admite claves de presentacion adicionales, por ejemplo:

- `environment_label`: `PRODUCCION` / `PRUEBAS` (si no se envia se deduce de la clave de acceso).
- `emission_label`: `NORMAL`.
- `company.logo_base64` o `company.logo_path`: logotipo del emisor.
- Subtotales por tarifa: `subtotal_15`, `subtotal_5`, `subtotal_0`, etc. (si no se envian, se calculan desde `tax_totals`).

## Codigo de barras

La clave de acceso se renderiza como codigo de barras Code128 (PNG) y se incrusta como data URI en el encabezado del RIDE. No requiere configuracion.

## Configuracion del PDF

```php
use MTZ\Toolkit\RideGenerator\Config\RidePdfConfig;
use MTZ\Toolkit\RideGenerator\Renders\DompdfRideRenderer;
use MTZ\Toolkit\RideGenerator\Renders\TwigRideTemplateRenderer;
use MTZ\Toolkit\RideGenerator\RideGenerator;

$config = new RidePdfConfig(
    format: 'A4',
    orientation: 'P',
    marginLeft: 12,
    marginRight: 12,
    marginTop: 12,
    marginBottom: 12,
    defaultFont: 'Helvetica',
    tempDir: '',            // vacio usa el temporal del sistema
    templatesPath: null,    // null usa las plantillas incluidas
);

$generator = new RideGenerator(
    templateRenderer: new TwigRideTemplateRenderer($config),
    renderer: new DompdfRideRenderer($config),
);
```

| Opcion | Default | Descripcion |
| --- | --- | --- |
| `format` | `A4` | Tamano de pagina |
| `orientation` | `P` | `P` vertical, `L` horizontal |
| `marginLeft/Right/Top/Bottom` | `12` | Margenes en milimetros |
| `defaultFont` | `Helvetica` | Fuente por defecto |
| `tempDir` | `''` | Directorio temporal de Dompdf |
| `templatesPath` | `null` | Ruta a plantillas Twig propias |

## Plantillas propias

Las plantillas incluidas viven en `src/RideGenerator/Resources/views`. Para personalizarlas, copia esa carpeta a tu proyecto y pasa la ruta en `templatesPath`. El layout base es `layouts/ride.html.twig` y el encabezado comun `partials/header.html.twig`.

## Guardar el RIDE

Combina el RIDE con [Storage](/modules/storage) para persistir el PDF junto al XML:

```php
$store->putRidePdf($ownerKey, $date, $accessKey, $pdf->content);
```
