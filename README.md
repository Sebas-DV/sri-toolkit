# SRI Toolkit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](https://packagist.org/packages/matiz-studio-creative/sri-toolkit)
[![Total Downloads](https://img.shields.io/packagist/dt/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](https://packagist.org/packages/matiz-studio-creative/sri-toolkit)
[![License](https://img.shields.io/packagist/l/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/matiz-studio-creative/sri-toolkit.svg?style=flat-square)](composer.json)

PHP toolkit for Ecuador SRI electronic documents. It helps generate access keys, build XML documents, sign them with XAdES-BES using PKCS#12 certificates, and send signed XML to the SRI reception and authorization web services.

## Documentation

Full documentation is available at [sri-toolkit.matizstudiocreative.com](https://sri-toolkit.matizstudiocreative.com).

## Features

- Electronic signing with PKCS#12 certificate files (`.p12` / `.pfx`) using the SRI-compatible XAdES-BES flow.
- Certificate metadata extraction for issuer, serial number and RSA public key material required by the XML signature.
- XML generation for the main SRI electronic documents: invoices, purchase settlements, credit notes, debit notes, delivery guides and withholding receipts.
- XML structures aligned with the official SRI document formats and ready for signing.
- SOAP client for SRI reception and authorization web services.
- Automatic 49-digit SRI access key generation with the modulo 11 verification digit.
- Typed reception and authorization response parsing.
- Support for SRI testing and production environments.
- Testable internals through injectable SOAP, clock, signer and sleeper dependencies.

## Requirements

- PHP >= 8.2
- PHP extensions:
  - `ext-soap`
  - `ext-openssl`
  - `ext-dom`
  - `ext-libxml`
- Composer

The CI suite currently runs against PHP 8.2, 8.3, 8.4 and 8.5.

## Installation

```bash
composer require matiz-studio-creative/sri-toolkit
```

## Supported Documents

Access key generation exposes the six SRI electronic document codes from the official offline technical sheet:

| Code | Document | Access key |
| --- | --- | --- |
| `01` | Invoice | `DocumentType::Invoice` |
| `03` | Purchase settlement | `DocumentType::PurchaseSettlement` |
| `04` | Credit note | `DocumentType::CreditNote` |
| `05` | Debit note | `DocumentType::DebitNote` |
| `06` | Remission guide | `DocumentType::RemissionGuide` |
| `07` | Withholding receipt | `DocumentType::RetentionVoucher` |

XML generation currently supports:

| Code | Document | Enum | XML version |
| --- | --- | --- | --- |
| `01` | Invoice | `XmlDocumentType::Invoice` | `2.1.0` |
| `03` | Purchase settlement | `XmlDocumentType::PurchaseSettlement` | `1.1.0` |
| `04` | Credit note | `XmlDocumentType::CreditNote` | `1.1.0` |
| `05` | Debit note | `XmlDocumentType::DebitNote` | `1.0.0` |
| `06` | Delivery guide | `XmlDocumentType::DeliveryGuide` | `1.1.0` |
| `07` | Withholding receipt | `XmlDocumentType::WithholdingReceipt` | `2.0.0` |

## Catalogs

Common document codes are available through a runtime-overridable catalog registry based on the official offline technical sheet v2.26 and the ICE annex.

```php
use MTZ\Toolkit\Catalogs\Catalogs;

$catalogRegistry = Catalogs::registry();

$catalogRegistry->get('vat-rates', '4');        // VAT 15%
$catalogRegistry->list('payment-methods');      // [{ code: '01', ... }, ...
$catalogRegistry->getMeta('vat-rates');         // source, updatedAt, notes
$catalogRegistry->listCatalogs();            // catalog names

$catalogRegistry->override('vat-rates', [
    '4' => ['code' => '4', 'description' => 'VAT 16%', 'rate' => 16],
]);

$catalogRegistry->reset('vat-rates');
```

PHP reserves `list` as a declared method name, so `CatalogRegistry` also exposes `entries('payment-methods')` for static-analysis-friendly code. The magic `$catalogRegistry->list(...)` call is supported for compatibility with the TypeScript API shape.

### Common Document Codes

Identification types (`customer.identification_type`, `subject.identification_type`, carriers):

| Code | Type |
| --- | --- |
| `04` | Taxpayer ID |
| `05` | National ID |
| `06` | Passport |
| `07` | Final consumer |
| `08` | Foreign ID |

VAT (`percentage_code` when tax `code` is `2`):

| Code | Rate | Notes |
| --- | --- | --- |
| `0` | 0% | VAT 0% |
| `2` | 12% | Historical |
| `3` | 14% | Historical |
| `4` | 15% | Common current rate |
| `5` | 5% | Added by official sheet v2.26 |
| `6` | 0% | Not subject to tax |
| `7` | 0% | VAT exempt |
| `8` | variable | Differentiated VAT |
| `10` | 13% | Added by official sheet v2.26 |

Payment methods:

| Code | Description |
| --- | --- |
| `01` | No financial system used |
| `15` | Debt compensation |
| `16` | Debit card |
| `17` | Electronic money |
| `18` | Prepaid card |
| `19` | Credit card |
| `20` | Other financial system method |
| `21` | Title endorsement |

VAT withholding (`code: '2'`, `withholding_code`):

| Code | Rate |
| --- | --- |
| `9` | 10% |
| `10` | 20% |
| `1` | 30% |
| `11` | 50% |
| `2` | 70% |
| `3` | 100% |
| `7` | 0%, zero withholding |
| `8` | 0%, withholding does not apply |

Full catalog: use `$catalogRegistry->list('vat-withholding')`.

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
        data: [
            'date' => '13/05/2026',
            'sequential' => '000000025',
            'company' => [
                'ruc' => '1790012345001',
                'legal_name' => 'MTZ TEST S.A.',
                'trade_name' => 'MTZ TEST',
                'head_office_address' => 'Quito',
            ],
            'establishment' => [
                'code' => '001',
            ],
            'emission_point' => [
                'code' => '001',
            ],
            'customer' => [
                'identification_type' => '05',
                'identification_number' => '1710034065',
                'name' => 'CONSUMIDOR FINAL',
                'address' => 'Quito',
            ],
            'establishment_address' => 'Quito',
            'requires_accounting' => 'NO',
            'total_without_taxes' => '10.00',
            'total_discount' => '0.00',
            'tax_totals' => [
                [
                    'code' => '2',
                    'percentage_code' => '4',
                    'taxable_base' => '10.00',
                    'value' => '1.50',
                ],
            ],
            'tip' => '0.00',
            'total_amount' => '11.50',
            'currency' => 'DOLAR',
            'payments' => [
                [
                    'method' => '01',
                    'total' => '11.50',
                ],
            ],
            'details' => [
                [
                    'main_code' => 'P001',
                    'description' => 'Producto de prueba',
                    'quantity' => '1.00',
                    'unit_price' => '10.00',
                    'discount' => '0.00',
                    'total_without_tax' => '10.00',
                    'taxes' => [
                        [
                            'code' => '2',
                            'percentage_code' => '4',
                            'rate' => '15.00',
                            'taxable_base' => '10.00',
                            'value' => '1.50',
                        ],
                    ],
                ],
            ],
            'additional_info' => [
                'Email' => 'cliente@example.com',
            ],
        ],
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
    ),
);

$result = $sender->send(
    accessKey: $accessKey,
    signedXml: $signedXml,
);

if (! $result->success) {
    throw new RuntimeException($result->error ?? 'SRI document was not authorized.');
}

$authorizedXml = $result->authorizationResult?->authorizedDocument?->xml;
```

## Generate an Access Key

```php
use MTZ\Toolkit\AccessKeyGenerator\AccessKeyGenerator;
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment;

$accessKey = (new AccessKeyGenerator())->generate(
    AccessKeyData::make(
        emissionDate: '2026-05-13',
        documentType: DocumentType::Invoice,
        ruc: '1790012345001',
        environment: Environment::Testing,
        sequential: 25,
        numericCode: '12345678',
        establishmentCode: '001',
        emissionPointCode: '001',
    ),
);
```

If `numericCode` is omitted, the package generates a random 8-digit numeric code.

## Generate XML

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

The payload shape depends on the selected document type. Invalid or incomplete payloads throw XML generation exceptions.

## Sign XML

```php
use MTZ\Toolkit\Signer\Signer;

$signedXml = (new Signer(
    certificatePath: '/secure/path/certificate.p12',
    certificatePassword: getenv('SRI_CERTIFICATE_PASSWORD') ?: '',
))
    ->loadXml($xml)
    ->sign();
```

The XML root must contain the expected SRI document id:

```xml
<factura id="comprobante" version="2.1.0">
```

The signer uses the SRI-compatible XMLDSig/XAdES-BES structure implemented by this package.

## Send to SRI

```php
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\Environment;
use MTZ\Toolkit\Sender\Sender;

$sender = new Sender(
    config: new SenderConfig(
        environment: Environment::Production,
        maxAttempts: 5,
        retryDelay: 1,
        sendDelay: 3,
        soapOptions: [
            'trace' => 0,
        ],
    ),
);

$result = $sender->send($accessKey, $signedXml);

if ($result->success) {
    $authorizedDocument = $result->authorizationResult?->authorizedDocument;
}
```

The sender uses the official SRI WSDL URLs for testing and production based on `SenderConfig`.

## Development

Install dependencies:

```bash
composer install
pnpm install
```

Run tests:

```bash
composer test
```

Run the full quality suite:

```bash
composer analyze
```

Useful commands:

```bash
composer cs:test
composer cs
composer stan
composer rector:test
composer rector
composer audit
```

The documentation site is built with VitePress:

```bash
pnpm docs:dev
pnpm docs:build
```

## Security

This package handles private keys, certificate passwords, signed XML and taxpayer data. Store certificates outside the repository and web root, keep passwords in a secret manager or protected environment variable, and avoid logging signed XML or SOAP traces in production.

See [SECURITY.md](SECURITY.md) for the full security policy and the [security guidance](https://sri-toolkit.matizstudiocreative.com/modules/signer) in the documentation.

## License

This package is open-sourced software licensed under the MIT license.
