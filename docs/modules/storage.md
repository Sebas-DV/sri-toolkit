# Storage y DocumentStore

El modulo de almacenamiento persiste los artefactos de cada comprobante (XML generado, firmado, autorizado, respuestas y RIDE) en disco local o en Amazon S3, con una estructura de rutas uniforme.

## Backends

Ambos backends implementan `DocumentStorageInterface` (`put`, `get`, `exists`, `delete`).

### Local

```php
use MTZ\Toolkit\Storage\LocalDocumentStorage;

$storage = new LocalDocumentStorage('/ruta/base');
$storage->put('sri/documents/1790012345001/2026/05/<clave>/ride.pdf', $bytes);
$contenido = $storage->get('sri/documents/1790012345001/2026/05/<clave>/ride.pdf');
$storage->exists($path);
$storage->delete($path);
```

Crea los directorios de forma automatica y rechaza rutas con salto de directorio (`..`).

### S3

```php
use Aws\S3\S3Client;
use MTZ\Toolkit\Storage\S3DocumentStorage;

$storage = new S3DocumentStorage(
    client: new S3Client([/* region, credentials, ... */]),
    bucket: 'mi-bucket',
    prefix: 'tenant-a',   // opcional
);
```

`S3DocumentStorage` implementa ademas `TemporaryUrlDocumentStorageInterface`:

```php
$url = $storage->temporaryUrl($path, new DateTimeImmutable('+10 minutes'));
```

## DocumentStore

`DocumentStore` orquesta las rutas por artefacto sobre cualquier backend.

```php
use MTZ\Toolkit\Documents\DocumentStore;

$store = new DocumentStore($storage);

$date = new DateTimeImmutable('2026-05-13');
$owner = '1790012345001';

$store->putGeneratedXml($owner, $date, $accessKey, $generatedXml);
$store->putSignedXml($owner, $date, $accessKey, $signedXml);
$store->putAuthorizedXml($owner, $date, $accessKey, $authorizedXml);
$store->putReceptionResponse($owner, $date, $accessKey, $result->receptionResult->toArray());
$store->putAuthorizationResponse($owner, $date, $accessKey, $result->authorizationResult->toArray());
$store->putRidePdf($owner, $date, $accessKey, $pdf->content);

$xml = $store->get($path);
```

Cada metodo `put*` devuelve la ruta donde se guardo el artefacto.

## Estructura de rutas

```
sri/documents/{ownerKey}/{ano}/{mes}/{claveAcceso}/{artefacto}
```

Los segmentos `ownerKey` y `claveAcceso` se sanean (solo `A-Za-z0-9_-.`). Artefactos:

| Artefacto | Archivo |
| --- | --- |
| XML generado | `generated.xml` |
| XML firmado | `signed.xml` |
| XML autorizado | `authorized.xml` |
| Respuesta de recepcion | `reception-response.json` |
| Respuesta de autorizacion | `authorization-response.json` |
| RIDE | `ride.pdf` |

## Backend propio

Implementa `DocumentStorageInterface` para usar otro backend (por ejemplo, base de datos o almacenamiento en memoria) y pasalo a `DocumentStore`.
