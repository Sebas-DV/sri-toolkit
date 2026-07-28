# Pipeline end-to-end

`DocumentPipeline` ejecuta todo el flujo en una sola llamada: generar, validar XSD, firmar, enviar, generar el RIDE y guardar.

## Ejemplo completo

```php
use MTZ\Toolkit\Documents\DocumentStore;
use MTZ\Toolkit\Pipeline\Data\DocumentEmission;
use MTZ\Toolkit\Pipeline\DocumentPipeline;
use MTZ\Toolkit\Pipeline\Signers\CertificateDocumentSigner;
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\RideGenerator;
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\Environment;
use MTZ\Toolkit\Sender\Sender;
use MTZ\Toolkit\Storage\LocalDocumentStorage;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;

$pipeline = new DocumentPipeline(
    signer: new CertificateDocumentSigner('/secure/certificate.p12', getenv('SRI_CERTIFICATE_PASSWORD') ?: ''),
    sender: new Sender(new SenderConfig(environment: Environment::Production)),
    rideGenerator: new RideGenerator(),
    documentStore: new DocumentStore(new LocalDocumentStorage('/var/comprobantes')),
);

$emission = new DocumentEmission(
    xml: XmlGenerationData::make(
        documentType: XmlDocumentType::Invoice,
        environment: XmlEnvironment::Production,
        accessKey: $accessKey,
        data: $invoicePayload,
    ),
    ride: RideData::make(RideDocumentType::Invoice, $accessKey, $ridePayload),
    ownerKey: '1790012345001',
);

$result = $pipeline->emit($emission);

if (! $result->success) {
    // $result->schemaErrors si fallo la validacion XSD
    // $result->error si fallo el envio
    return;
}

$result->send?->authorizationResult?->authorizedDocument?->xml;
$result->ride?->content;
$result->storedPaths; // rutas de cada artefacto guardado
```

## Etapas opcionales

Pasa `null` en cualquier dependencia para saltarla. Por ejemplo, generar, validar y guardar sin enviar:

```php
$pipeline = new DocumentPipeline(
    documentStore: new DocumentStore($storage),
    // sin signer ni sender
);
```

Detalle de cada etapa y del resultado en [Pipeline](/modules/pipeline).
