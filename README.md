# SRI Toolkit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](https://packagist.org/packages/matiz-studio-creative/sri-toolkit)
[![Total Downloads](https://img.shields.io/packagist/dt/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](https://packagist.org/packages/matiz-studio-creative/sri-toolkit)
[![Build Status](https://img.shields.io/github/actions/workflow/status/Sebas-DV/sri-toolkit/tests.yml?branch=main&style=flat-square&label=tests)](https://github.com/Sebas-DV/sri-toolkit/actions)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg?style=flat-square)](phpstan.neon)
[![License](https://img.shields.io/packagist/l/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](composer.json)

A modern, robust, and framework-agnostic PHP toolkit for managing Ecuador's SRI (*Servicio de Rentas Internas*) electronic billing lifecycle.

It covers the complete workflow: 49-digit access key generation, XML generation with automated decimal totals, offline XSD and identity validation, XAdES-BES digital signing with PKCS#12 certificates, direct SOAP communication (reception, authorization, status querying, and batch processing), RIDE PDF rendering, and document storage.

---

## Documentation

Comprehensive documentation, architectural details, and guides are available at:
👉 **[sri-toolkit.matizstudiocreative.com](https://sri-toolkit.matizstudiocreative.com)**

---

## Features

- **Access Keys**: Generation of the official 49-digit SRI access key using the modulo 11 algorithm.
- **XML Generation**: Compliant structure for the 6 SRI document types (Invoices, Purchase Settlements, Credit Notes, Debit Notes, Delivery Guides, and Withholding Receipts).
- **Invoice Variants**: Native support for export invoices (foreign trade), reimbursements, third-party charges, delivery-guide substitutes, fiscal machines, and vehicle plates.
- **Exact Decimal Arithmetic**: Automated balance and tax calculation derived from line items using exact decimal arithmetic (`brick/money`), eliminating calculation-difference rejections.
- **Offline Validation**: Pre-flight validation against official SRI XSD schemas and national ID check digits (Cédula / RUC) prior to signing and transmission.
- **XAdES-BES Signing**: Strict XMLDSig/XAdES-BES compliant digital signature using PKCS#12 (`.p12` / `.pfx`) certificates.
- **SOAP Gateway**: Fully typed client for Reception, Authorization, Status Querying (`ConsultaComprobante`), and Batch processing (up to 50 documents / 500 kB).
- **Typed Responses & Error Categorization**: Real-time classification of SRI error codes into retryable states, blocking impediments, or asynchronous processing queues.
- **RIDE PDF Generation**: Pre-built templates (rendered via Dompdf with Code128 barcodes) for all 6 document types.
- **Multi-Driver Storage**: Unified `DocumentStore` supporting local disk and Amazon S3 (with presigned URLs).
- **Encrypted Certificates**: Utilities for storing PKCS#12 credentials with AES-256-GCM encryption at rest.
- **End-to-End Pipeline**: Unified emission orchestrator to generate, validate, sign, transmit, render RIDE, and store in a single execution flow.
- **Testable Architecture**: Fully mockable dependencies via interfaces (SOAP, Signer, Clock, Storage, Sleeper).

---

## Requirements

- **PHP**: >= 8.2 (Tested on PHP 8.2, 8.3, 8.4, and 8.5)
- **Extensions**: `ext-soap`, `ext-openssl`, `ext-dom`, `ext-libxml`
- **Package Manager**: [Composer](https://getcomposer.org/)

---

## Installation

Install the package via Composer:

```bash
composer require matiz-studio-creative/sri-toolkit
```

---

## Supported Documents

| Code | Document Type | XML Version | Supported Features |
| :---: | :--- | :---: | :--- |
| `01` | **Invoice** (*Factura*) | `2.1.0` | Standard, Export, Reimbursements, Third-party charges |
| `03` | **Purchase Settlement** (*Liquidación de Compra*) | `1.1.0` | Standard, Reimbursements |
| `04` | **Credit Note** (*Nota de Crédito*) | `1.1.0` | Total/Partial modifications |
| `05` | **Debit Note** (*Nota de Débito*) | `1.0.0` | Value adjustments |
| `06` | **Delivery Guide** (*Guía de Remisión*) | `1.1.0` | Transportation and route tracking |
| `07` | **Withholding Receipt** (*Comprobante de Retención*) | `2.0.0` | Income Tax & VAT withholding |

---

## Quick Start

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

// 1. Generate 49-digit Access Key
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

// 2. Generate XML Structure
$generated = (new XMLMaker())->generate(
    XmlGenerationData::make(
        documentType: XmlDocumentType::Invoice,
        environment: XmlEnvironment::Testing,
        accessKey: $accessKey,
        data: $invoicePayload, // Check documentation for payload structure
    ),
);
$xml = $generated->toString();

// 3. Perform Offline Validation against official XSD
$errors = (new XsdValidator())->validate($xml, XmlDocumentType::Invoice);
if ($errors !== []) {
    throw new RuntimeException("Invalid XML Schema:\n" . implode("\n", $errors));
}

// 4. Digital Signature (XAdES-BES)
$signedXml = (new Signer(
    certificatePath: '/secure/path/certificate.p12',
    certificatePassword: getenv('SRI_CERTIFICATE_PASSWORD') ?: '',
))->loadXml($xml)->sign();

// 5. Transmit to SRI Web Services
$sender = new Sender(new SenderConfig(environment: SenderEnvironment::Testing));
$result = $sender->send($accessKey, $signedXml);

if (! $result->success) {
    throw new RuntimeException($result->error ?? 'SRI document authorization failed.');
}

$authorizedXml = $result->authorizationResult?->authorizedDocument?->xml;
```

---

## Core Modules

### 1. Offline Validation
Prevent unnecessary network round-trips and official rejections by validating locally:

```php
use MTZ\Toolkit\XMLMaker\Validation\IdentificationValidator;
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;

$xsdErrors = (new XsdValidator())->validate($xml, XmlDocumentType::Invoice);
$isValidRuc = (new IdentificationValidator())->isValidRuc('1760013210001');
```

*See [Validation Documentation](https://sri-toolkit.matizstudiocreative.com/modules/validation).*

### 2. Status Inquiries & Error Classification
Inspect detailed messages returned by the SRI:

```php
$result = $sender->send($accessKey, $signedXml);

foreach ($result->authorizationResult?->messages ?? [] as $message) {
    if ($message->sriCode()?->isProcessing()) {
        // Code 70: SRI batch in queue, do not re-emit immediately
    }
    if ($message->sriCode()?->isImpediment()) {
        // Critical error: fix data before resending
    }
}
```

*See [Sender Module](https://sri-toolkit.matizstudiocreative.com/modules/sender) and [SRI Error Codes](https://sri-toolkit.matizstudiocreative.com/reference/error-codes).*

### 3. RIDE PDF Generation
Render print-ready RIDE documents:

```php
use MTZ\Toolkit\RideGenerator\Data\RideData;
use MTZ\Toolkit\RideGenerator\Enums\RideDocumentType;
use MTZ\Toolkit\RideGenerator\RideGenerator;

$pdf = (new RideGenerator())->generate(
    RideData::make(RideDocumentType::Invoice, $accessKey, $ridePayload),
);

$pdf->saveTo('/var/documents/ride_invoice.pdf');
```

*See [RideGenerator](https://sri-toolkit.matizstudiocreative.com/modules/ride-generator).*

### 4. Storage
Save artifacts to local storage or Amazon S3:

```php
use MTZ\Toolkit\Documents\DocumentStore;
use MTZ\Toolkit\Storage\LocalDocumentStorage;

$store = new DocumentStore(new LocalDocumentStorage('/var/documents'));
$store->putSignedXml($ruc, new DateTimeImmutable(), $accessKey, $signedXml);
$store->putRidePdf($ruc, new DateTimeImmutable(), $accessKey, $pdf->content);
```

*See [Storage](https://sri-toolkit.matizstudiocreative.com/modules/storage).*

### 5. End-to-End Execution Pipeline
Execute all phases in a consolidated workflow:

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

*See [Pipeline Module](https://sri-toolkit.matizstudiocreative.com/modules/pipeline).*

### 6. Catalogs
Common SRI codes through a runtime-overridable registry based on technical sheet v2.34:

```php
use MTZ\Toolkit\Catalogs\Catalogs;

$registry = Catalogs::registry();
$registry->get('vat-rates', '4'); // VAT 15%
$registry->list('payment-methods');
```

*See [Catalogs](https://sri-toolkit.matizstudiocreative.com/modules/catalogs).*

---

## Development & Testing

Run the test suite and static analysis tools locally:

```bash
# Clone the repository
git clone https://github.com/Sebas-DV/sri-toolkit.git
cd sri-toolkit

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis and linting
composer stan
composer rector
composer cs
```

The documentation site is built with VitePress (`pnpm docs:dev`, `pnpm docs:build`).

---

## Contributing

Contributions are welcome! Please feel free to submit issues, feature requests, or pull requests.

1. Fork the repository (`https://github.com/Sebas-DV/sri-toolkit/fork`).
2. Create your feature branch (`git checkout -b feature/amazing-feature`).
3. Commit your changes with descriptive messages (`git commit -m 'Add support for withholding type X'`).
4. Push to the branch (`git push origin feature/amazing-feature`).
5. Open a Pull Request against `main`.

Please review [CONTRIBUTING.md](CONTRIBUTING.md) for full development standards and guidelines.

---

## Security Policy

This package handles private keys, certificate passwords, signed XML, and taxpayer data. 
- Store certificates outside the repository and web root.
- Keep passwords in a secret manager or protected environment variables.
- Avoid logging signed XML or SOAP traces in production.
- Review [SECURITY.md](SECURITY.md) for vulnerability disclosure protocols.

---

## Disclaimer

This package is an open-source project and is **not** officially affiliated with or endorsed by the *Servicio de Rentas Internas (SRI)* of Ecuador.

---

## License

Open-sourced software licensed under the [MIT License](LICENSE).
