# SRI Toolkit

Toolkit PHP para trabajar con comprobantes electronicos del SRI Ecuador.

SRI Toolkit cubre las piezas principales de un flujo de facturacion electronica:

- Generar la clave de acceso de 49 digitos.
- Construir XML de comprobantes SRI.
- Firmar XML con certificados PKCS#12 mediante XAdES-BES.
- Enviar comprobantes firmados a los servicios SOAP de recepcion y autorizacion del SRI.
- Leer respuestas de recepcion y autorizacion como objetos tipados.

## Estado del paquete

| Area | Estado |
| --- | --- |
| Clave de acceso | Implementado |
| XML de factura | Implementado |
| XML de liquidacion de compra | Implementado |
| XML de nota de credito | Implementado |
| XML de nota de debito | Implementado |
| XML de guia de remision | Implementado |
| XML de comprobante de retencion | Implementado |
| Firma XAdES-BES | Implementado |
| Envio SOAP SRI | Implementado |

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
- [XMLMaker](/modules/xml-maker): genera XML de factura, liquidacion de compra, notas de credito/debito, guia de remision y comprobante de retencion.
- [Catalogos](/modules/catalogs): consulta y sobreescribe codigos frecuentes.
- [Signer](/modules/signer): firma XML con XAdES-BES.
- [Sender](/modules/sender): consume los Web Services del SRI.

## Desarrollo

```bash
composer install
pnpm install
composer analyze
pnpm docs:build
```
