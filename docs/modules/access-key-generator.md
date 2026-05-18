# AccessKeyGenerator

`AccessKeyGenerator` genera la clave de acceso SRI de 49 digitos a partir de los datos del comprobante.

## API principal

```php
use MTZ\Toolkit\AccessKeyGenerator\AccessKeyGenerator;
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;

$accessKey = (new AccessKeyGenerator())->generate($data);
```

`$data` es una instancia de `AccessKeyData`.

## Crear datos de clave

```php
use MTZ\Toolkit\AccessKeyGenerator\Data\AccessKeyData;
use MTZ\Toolkit\AccessKeyGenerator\Enums\DocumentType;
use MTZ\Toolkit\AccessKeyGenerator\Enums\Environment;

$data = AccessKeyData::make(
    emissionDate: '2026-05-13',
    documentType: DocumentType::Invoice,
    ruc: '1790012345001',
    environment: Environment::Testing,
    sequential: 25,
    numericCode: '12345678',
    establishmentCode: '001',
    emissionPointCode: '001',
);
```

## Campos

| Campo | Requerido | Descripcion |
| --- | --- | --- |
| `emissionDate` | Si | Fecha aceptada por `DateTimeImmutable` |
| `documentType` | Si | Tipo de comprobante SRI |
| `ruc` | Si | RUC de 13 digitos |
| `environment` | Si | `Testing` o `Production` |
| `sequential` | Si | Secuencial, se rellena a 9 digitos |
| `numericCode` | No | Codigo numerico de 8 digitos; si falta se genera aleatoriamente |
| `establishmentCode` | No | Codigo de establecimiento, por defecto `001` |
| `emissionPointCode` | No | Punto de emision, por defecto `001` |
| `emissionType` | No | Tipo de emision, por defecto normal |

## Tipos de documento

```php
DocumentType::Invoice;
DocumentType::PurchaseSettlement;
DocumentType::CreditNote;
DocumentType::DebitNote;
DocumentType::RemissionGuide;
DocumentType::RetentionVoucher;
```

## Ambientes

```php
Environment::Testing;    // 1
Environment::Production; // 2
```

## Ejemplo con resultado conocido

```php
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

// 1305202601179001234500110010010000000251234567817
```

## Validaciones

El generador valida longitud numerica de RUC, establecimiento, punto de emision, secuencial y codigo numerico. Cuando un campo es invalido lanza `AccessKeyException`.
