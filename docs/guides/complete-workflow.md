# Workflow completo

Este ejemplo junta las cuatro piezas del paquete: clave de acceso, XML, firma y envio.

```php
<?php

use MTZ\Toolkit\AccessKeyGenerator\AccessKeyGenerator;
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment as AccessKeyEnvironment;
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\Environment as SenderEnvironment;
use MTZ\Toolkit\Sender\Sender;
use MTZ\Toolkit\Signer\Signer;
use MTZ\Toolkit\XMLMaker\Data\XmlGenerationData;
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Enums\XmlEnvironment;
use MTZ\Toolkit\XMLMaker\XMLMaker;

$accessKey = (new AccessKeyGenerator())->generate(
    AccessKeyData::make(
        emissionDate: '2026-05-13',
        documentType: DocumentType::Invoice,
        ruc: '1790012345001',
        environment: AccessKeyEnvironment::Testing,
        sequential: 25,
        numericCode: '12345678',
        establishmentCode: '001',
        emissionPointCode: '001',
    ),
);

$generatedXml = (new XMLMaker())->generate(
    XmlGenerationData::make(
        documentType: XmlDocumentType::Invoice,
        environment: XmlEnvironment::Testing,
        accessKey: $accessKey,
        data: $invoicePayload,
    ),
);

$signedXml = (new Signer(
    certificatePath: '/secure/path/certificate.p12',
    certificatePassword: getenv('SRI_CERTIFICATE_PASSWORD') ?: '',
))
    ->loadXml($generatedXml->toString())
    ->sign();

$sender = new Sender(
    config: new SenderConfig(
        environment: SenderEnvironment::Testing,
        maxAttempts: 5,
        retryDelay: 1,
        sendDelay: 3,
    ),
);

$result = $sender->send($accessKey, $signedXml);

if (! $result->success) {
    throw new RuntimeException($result->error ?? 'No se pudo autorizar el comprobante.');
}

$authorizedXml = $result->authorizationResult?->authorizedDocument?->xml;
```

Usa el payload de [Ejemplo de factura](/guides/invoice-example) como `$invoicePayload`.

## Manejo de errores

```php
try {
    $result = $sender->send($accessKey, $signedXml);
} catch (Throwable $exception) {
    // Errores de validacion local, XML, certificado o argumentos invalidos.
    throw $exception;
}

if (! $result->success) {
    // Errores reportados por recepcion/autorizacion SRI o fallos SOAP controlados.
    $message = $result->error;
}
```

## Persistencia recomendada

Guarda al menos:

- Clave de acceso.
- XML generado antes de firma.
- XML firmado.
- Estado de recepcion.
- Estado de autorizacion.
- XML autorizado devuelto por el SRI, si existe.
- Fecha de autorizacion.

Evita persistir passwords de certificados o trazas SOAP completas.
