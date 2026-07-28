# SRI Toolkit

Toolkit PHP para trabajar con comprobantes electronicos del SRI Ecuador.

SRI Toolkit cubre el flujo completo de facturacion electronica:

- Generar la clave de acceso de 49 digitos.
- Construir XML de los seis comprobantes, con calculo automatico de totales.
- Validar el XML offline contra el XSD oficial antes de enviar.
- Firmar XML con certificados PKCS#12 mediante XAdES-BES.
- Enviar comprobantes firmados a recepcion y autorizacion (individual o por lote).
- Consultar el estado de un comprobante y leer respuestas como objetos tipados.
- Generar el RIDE en PDF y guardar todos los artefactos en disco local o S3.
- Orquestar todo el flujo con un pipeline de una sola llamada.

## Estado del paquete

| Area | Estado |
| --- | --- |
| Clave de acceso (modulo 11) | Implementado |
| XML de los seis comprobantes | Implementado |
| Variantes de factura (exportacion, reembolso, rubros de terceros, sustitutiva) | Implementado |
| Calculo automatico de totales | Implementado |
| Validacion XSD offline | Implementado |
| Validacion de identificacion (cedula / RUC) | Implementado |
| Firma XAdES-BES | Implementado |
| Envio SOAP: recepcion y autorizacion | Implementado |
| Consulta de estado | Implementado |
| Envio por lote | Implementado |
| Codigos de mensaje tipados | Implementado |
| RIDE en PDF (seis tipos) | Implementado |
| Almacenamiento local y S3 | Implementado |
| Gestion de certificados cifrados | Implementado |
| Pipeline end-to-end | Implementado |

## Requisitos

- PHP 8.2 o superior.
- Extensiones `soap`, `openssl`, `dom` y `libxml`.
- Composer.
- Un certificado digital PKCS#12 (`.p12` o `.pfx`) para firmar documentos reales.

## Instalacion rapida

```bash
composer require matiz-studio-creative/sri-toolkit
```

## Primer flujo

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

Sigue con [Primeros pasos](/getting-started) para generar el XML, firmarlo y enviarlo.

## Modulos

- [AccessKeyGenerator](/modules/access-key-generator): genera claves de acceso SRI.
- [XMLMaker](/modules/xml-maker): genera XML de los seis comprobantes, con calculo de totales y variantes.
- [Validacion](/modules/validation): XSD offline, identificacion (cedula/RUC) y totales.
- [Catalogos](/modules/catalogs): consulta y sobreescribe codigos frecuentes.
- [Signer](/modules/signer): firma XML con XAdES-BES.
- [Certificates](/modules/certificates): guarda certificados con la contrasena cifrada.
- [Sender](/modules/sender): recepcion, autorizacion, consulta y lote.
- [RideGenerator](/modules/ride-generator): genera el RIDE en PDF.
- [Storage](/modules/storage): guarda artefactos en disco local o S3.
- [Pipeline](/modules/pipeline): orquesta todo el flujo en una sola llamada.

Referencia: [Codigos de error](/reference/error-codes).

## Desarrollo

```bash
composer install
pnpm install
composer analyze
pnpm docs:build
```
