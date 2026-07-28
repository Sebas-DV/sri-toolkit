# SRI Toolkit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](https://packagist.org/packages/matiz-studio-creative/sri-toolkit)
[![Total Downloads](https://img.shields.io/packagist/dt/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](https://packagist.org/packages/matiz-studio-creative/sri-toolkit)
[![License](https://img.shields.io/packagist/l/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](composer.json)

PHP toolkit for Ecuador's SRI electronic documents. It covers the full lifecycle: access keys, XML generation with automatic totals, offline XSD validation, XAdES-BES signing with PKCS#12 certificates, SOAP delivery (reception, authorization, status query and batch), RIDE PDF generation, and artifact storage on local disk or S3.

## Documentation

Full documentation is available at [sri-toolkit.matizstudiocreative.com](https://sri-toolkit.matizstudiocreative.com).

## Features

- **Access keys**: 49-digit SRI access key generation with the modulo 11 check digit.
- **XML generation** for the six SRI documents: invoices, purchase settlements, credit notes, debit notes, delivery guides and withholding receipts.
- **Invoice variants**: export (foreign trade), reimbursement detail, third-party charges, delivery-guide substitute, fiscal machine and fuel plate.
- **Automatic totals**: totals are derived from the detail lines with exact decimal arithmetic (brick/money), keeping the XML consistent and preventing calculation-difference rejections.
- **Offline validation**: validate the generated XML against the bundled official SRI XSD, plus cédula/RUC identification check digits, before signing.
- **XAdES-BES signing** with PKCS#12 (`.p12` / `.pfx`) certificates, SRI-compatible.
- **SOAP delivery**: reception and authorization, status query (`ConsultaComprobante`), and batch authorization (up to 50 vouchers / 500 kB).
- **Typed responses**: reception, authorization, consultation and batch results, plus typed SRI message codes with retry/impediment/processing classification.
- **RIDE PDF** for the six document types (Twig templates rendered with Dompdf, Code128 barcode).
- **Storage**: local filesystem and Amazon S3 (with presigned URLs), and a `DocumentStore` that lays out every artifact under a consistent path.
- **Certificates**: store PKCS#12 certificates with the password encrypted at rest (AES-256-GCM).
- **Pipeline**: an end-to-end orchestrator (generate, validate, sign, send, RIDE, store) in a single call.
- Testable internals through injectable SOAP, clock, signer, storage and sleeper dependencies.

## Requirements

- PHP >= 8.2
- PHP extensions: `ext-soap`, `ext-openssl`, `ext-dom`, `ext-libxml`
- Composer

The CI suite runs against PHP 8.2, 8.3, 8.4 and 8.5.

## Installation

```bash
composer require matiz-studio-creative/sri-toolkit
```

## Supported documents

| Code | Document | XML version |
| --- | --- | --- |
| `01` | Invoice | `2.1.0` |
| `03` | Purchase settlement | `1.1.0` |
| `04` | Credit note | `1.1.0` |
| `05` | Debit note | `1.0.0` |
| `06` | Delivery guide | `1.1.0` |
| `07` | Withholding receipt | `2.0.0` |

## Quick start

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
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;
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

$generated = (new XMLMaker())->generate(
    XmlGenerationData::make(
        documentType: XmlDocumentType::Invoice,
        environment: XmlEnvironment::Testing,
        accessKey: $accessKey,
        data: $invoicePayload, // see docs/modules/xml-maker
    ),
);

$xml = $generated->toString();

// Offline verification before signing.
$errors = (new XsdValidator())->validate($xml, XmlDocumentType::Invoice);
if ($errors !== []) {
    throw new RuntimeException("Invalid XML:\n" . implode("\n", $errors));
}

$signedXml = (new Signer(
    certificatePath: '/secure/path/certificate.p12',
    certificatePassword: getenv('SRI_CERTIFICATE_PASSWORD') ?: '',
))->loadXml($xml)->sign();

$sender = new Sender(new SenderConfig(environment: SenderEnvironment::Testing));
$result = $sender->send($accessKey, $signedXml);

if (! $result->success) {
    throw new RuntimeException($result->error ?? 'SRI document was not authorized.');
}

$authorizedXml = $result->authorizationResult?->authorizedDocument?->xml;
```

## Generate XML

`XMLMaker` builds the XML for every document type and derives the totals from the detail lines by default (invoice, purchase settlement, credit note). Invoice variants (export, reimbursement, third-party charges, delivery-guide substitute) are emitted when their key is present in the payload.

```php
$generated = (new XMLMaker())->generate($xmlGenerationData);
$xml = $generated->toString();
```

See [XMLMaker](https://sri-toolkit.matizstudiocreative.com/modules/xml-maker).

## Validate offline

Catch most rejections in your own server before sending.

```php
use MTZ\Toolkit\XMLMaker\Validation\IdentificationValidator;
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;

$errors = (new XsdValidator())->validate($xml, XmlDocumentType::Invoice); // schema
$valid  = (new IdentificationValidator())->isValidRuc('1760013210001');   // check digits
```

See [Validation](https://sri-toolkit.matizstudiocreative.com/modules/validation).

## Sign

```php
$signedXml = (new Signer($certPath, $certPassword))->loadXml($xml)->sign();
```

## Send, query status and batch

```php
$result = $sender->send($accessKey, $signedXml);      // reception + authorization
$status = $sender->queryStatus($accessKey);           // AUTORIZADO / ANULADO / ...
$batch  = $sender->sendBatch($loteKey, $ruc, $signedXmls); // up to 50 vouchers
```

Interpret SRI messages with typed codes:

```php
foreach ($result->authorizationResult?->messages ?? [] as $message) {
    $message->sriCode()?->isProcessing(); // code 70: wait, do not resend
    $message->sriCode()?->isImpediment(); // resolve before resending
}
```

See [Sender](https://sri-toolkit.matizstudiocreative.com/modules/sender) and [error codes](https://sri-toolkit.matizstudiocreative.com/reference/error-codes).

## RIDE PDF

```php
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\RideGenerator;

$pdf = (new RideGenerator())->generate(
    RideData::make(RideDocumentType::Invoice, $accessKey, $ridePayload),
);

$pdf->saveTo('/path/ride.pdf');
```

See [RideGenerator](https://sri-toolkit.matizstudiocreative.com/modules/ride-generator).

## Store artifacts

```php
use MTZ\Toolkit\Documents\DocumentStore;
use MTZ\Toolkit\Storage\LocalDocumentStorage;

$store = new DocumentStore(new LocalDocumentStorage('/var/documents'));
$store->putSignedXml($ruc, new DateTimeImmutable(), $accessKey, $signedXml);
$store->putRidePdf($ruc, new DateTimeImmutable(), $accessKey, $pdf->content);
```

Local disk or Amazon S3 (with presigned URLs). See [Storage](https://sri-toolkit.matizstudiocreative.com/modules/storage).

## End-to-end pipeline

Generate, validate, sign, send, generate the RIDE and store everything in one call:

```php
use MTZ\Toolkit\Pipeline\Data\DocumentEmission;
use MTZ\Toolkit\Pipeline\DocumentPipeline;
use MTZ\Toolkit\Pipeline\Signers\CertificateDocumentSigner;

$pipeline = new DocumentPipeline(
    signer: new CertificateDocumentSigner($certPath, $certPassword),
    sender: new Sender(),
    rideGenerator: new RideGenerator(),
    documentStore: new DocumentStore($storage),
);

$result = $pipeline->emit(new DocumentEmission(
    xml: $xmlGenerationData,
    ride: RideData::make(RideDocumentType::Invoice, $accessKey, $ridePayload),
    ownerKey: '1790012345001',
));
```

See [Pipeline](https://sri-toolkit.matizstudiocreative.com/modules/pipeline).

## Catalogs

Common SRI codes (identification types, document types, payment methods, VAT rates, VAT withholding, tax codes, support codes and ICE codes) through a runtime-overridable registry, based on the official offline technical sheet v2.34.

```php
use MTZ\Toolkit\Catalogs\Catalogs;

$registry = Catalogs::registry();
$registry->get('vat-rates', '4');       // VAT 15%
$registry->list('payment-methods');
```

See [Catalogs](https://sri-toolkit.matizstudiocreative.com/modules/catalogs).

## Development

```bash
composer install
composer test
composer analyze
```

Useful commands: `composer cs`, `composer stan`, `composer rector`, `composer audit`. The documentation site is built with VitePress (`pnpm docs:dev`, `pnpm docs:build`).

## Security

This package handles private keys, certificate passwords, signed XML and taxpayer data. Store certificates outside the repository and web root, keep passwords in a secret manager or protected environment variable, and avoid logging signed XML or SOAP traces in production. See [SECURITY.md](SECURITY.md).

## License

Open-sourced software licensed under the MIT license.
