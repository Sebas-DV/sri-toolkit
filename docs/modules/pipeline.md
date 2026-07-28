# Pipeline

`DocumentPipeline` orquesta el flujo completo de un comprobante en una sola llamada: generar XML, validar contra el XSD, firmar, enviar (recepcion y autorizacion), generar el RIDE y guardar todos los artefactos. Cada etapa es opcional.

## Uso

```php
use MTZ\Toolkit\Pipeline\Data\DocumentEmission;
use MTZ\Toolkit\Pipeline\DocumentPipeline;
use MTZ\Toolkit\Pipeline\Signers\CertificateDocumentSigner;
use MTZ\Toolkit\Documents\DocumentStore;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\RideGenerator;
use MTZ\Toolkit\Sender\Sender;

$pipeline = new DocumentPipeline(
    signer: new CertificateDocumentSigner('/secure/certificate.p12', getenv('SRI_CERTIFICATE_PASSWORD') ?: ''),
    sender: new Sender(),
    rideGenerator: new RideGenerator(),
    documentStore: new DocumentStore($storage),
    // validator XsdValidator y XMLMaker por defecto
);

$result = $pipeline->emit(new DocumentEmission(
    xml: $xmlGenerationData,
    ride: RideData::make(RideDocumentType::Invoice, $accessKey, $ridePayload),
    ownerKey: '1790012345001',   // activa el guardado
));
```

## Etapas

En orden, cada etapa se ejecuta solo si su dependencia esta presente:

1. Generar XML con `XMLMaker`.
2. Validar contra el XSD (si hay `validator`). Si falla, corta antes de firmar.
3. Firmar (si hay `signer`).
4. Enviar recepcion y autorizacion (si hay `sender` y XML firmado).
5. Generar el RIDE (si hay `rideGenerator` y `ride`, y el envio fue exitoso).
6. Guardar cada artefacto (si hay `documentStore` y `ownerKey`).

Pasa `null` en cualquier dependencia para saltar esa etapa (por ejemplo, generar y validar sin enviar).

## Resultado

```php
$result->success;       // autorizado, o true si no hay sender configurado
$result->accessKey;
$result->generatedXml;
$result->signedXml;     // null si no se firmo
$result->send;          // SendResult
$result->ride;          // GeneratedRidePdf
$result->storedPaths;   // ['generated.xml' => '...', 'signed.xml' => '...', 'ride.pdf' => '...']
$result->schemaErrors;  // errores XSD si la validacion corto el flujo
$result->error;         // motivo de fallo
```

Solo se guarda `authorized.xml` cuando el SRI autoriza el comprobante.

## Firma

`CertificateDocumentSigner` adapta el [Signer](/modules/signer) a la costura `DocumentSignerInterface` del pipeline. Implementa esa interfaz para usar otra estrategia de firma (por ejemplo, un HSM).

```php
use MTZ\Toolkit\Pipeline\Contracts\DocumentSignerInterface;

final class MiFirmador implements DocumentSignerInterface
{
    public function sign(string $xml): string { /* ... */ }
}
```
