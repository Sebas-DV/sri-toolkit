# RIDE y almacenamiento

Genera el RIDE en PDF y guarda todos los artefactos del comprobante en disco local o S3.

## Generar el RIDE

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

$pdf->saveTo('/ruta/ride.pdf');
```

Detalles del payload y personalizacion de plantillas en [RideGenerator](/modules/ride-generator).

## Guardar artefactos

```php
use MTZ\Toolkit\Documents\DocumentStore;
use MTZ\Toolkit\Storage\LocalDocumentStorage;

$store = new DocumentStore(new LocalDocumentStorage('/var/comprobantes'));

$date = new DateTimeImmutable('2026-05-13');
$owner = '1790012345001';

$store->putGeneratedXml($owner, $date, $accessKey, $xml);
$store->putSignedXml($owner, $date, $accessKey, $signedXml);
$store->putAuthorizedXml($owner, $date, $accessKey, $authorizedXml);
$store->putRidePdf($owner, $date, $accessKey, $pdf->content);
```

## En S3 con URL temporal

```php
use Aws\S3\S3Client;
use MTZ\Toolkit\Storage\S3DocumentStorage;

$storage = new S3DocumentStorage(new S3Client([/* ... */]), 'mi-bucket');
$store = new DocumentStore($storage);

$path = $store->putRidePdf($owner, $date, $accessKey, $pdf->content);
$url = $storage->temporaryUrl($path, new DateTimeImmutable('+10 minutes'));
```

Ver [Storage](/modules/storage) para la estructura de rutas y todos los artefactos.
