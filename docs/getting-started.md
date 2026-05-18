# Primeros pasos

Esta guia muestra el flujo minimo para generar una factura, firmarla y enviarla al ambiente de pruebas del SRI.

## 1. Generar la clave de acceso

```php
<?php

use MTZ\Toolkit\AccessKeyGenerator\AccessKeyGenerator;
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment as AccessKeyEnvironment;

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
```

La clave generada tiene 49 digitos e incluye el digito verificador modulo 11.

## 2. Generar el XML

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

`$invoicePayload` debe contener los campos de factura documentados en [XMLMaker](/modules/xml-maker).

## 3. Firmar el XML

```php
use MTZ\Toolkit\Signer\Signer;

$signedXml = (new Signer(
    certificatePath: '/secure/path/certificate.p12',
    certificatePassword: getenv('SRI_CERTIFICATE_PASSWORD') ?: '',
))
    ->loadXml($xml)
    ->sign();
```

El XML debe tener el atributo `id="comprobante"` en el nodo raiz. `XMLMaker` lo agrega automaticamente.

## 4. Enviar al SRI

```php
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\Environment as SenderEnvironment;
use MTZ\Toolkit\Sender\Sender;

$sender = new Sender(
    config: new SenderConfig(
        environment: SenderEnvironment::Testing,
    ),
);

$result = $sender->send($accessKey, $signedXml);

if (! $result->success) {
    throw new RuntimeException($result->error ?? 'Comprobante no autorizado.');
}

$authorizedDocument = $result->authorizationResult?->authorizedDocument;
```

`send()` ejecuta recepcion y, si el comprobante fue recibido, consulta autorizacion.

## Produccion

Para produccion cambia los ambientes:

```php
AccessKeyEnvironment::Production;
XmlEnvironment::Production;
SenderEnvironment::Production;
```

Verifica tambien que uses datos reales, certificado vigente y secuenciales correctos.
