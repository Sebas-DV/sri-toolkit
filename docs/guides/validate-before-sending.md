# Validar antes de enviar (verificacion offline)

Validar el comprobante en tu servidor, antes de firmar y enviar, evita la mayoria de rechazos del SRI sin gastar llamadas de red. El toolkit incluye tres verificaciones offline.

## 1. Esquema XSD

Valida el XML generado contra el XSD oficial incluido en el paquete. Atrapa los errores 35 (documento invalido), 36 y 48.

```php
use MTZ\Toolkit\XMLMaker\Enums\XmlDocumentType;
use MTZ\Toolkit\XMLMaker\Validation\XsdValidator;

$validator = new XsdValidator();
$errors = $validator->validate($xml, XmlDocumentType::Invoice);

if ($errors !== []) {
    throw new RuntimeException("XML no conforme:\n" . implode("\n", $errors));
}
```

## 2. Identificacion del comprador

Valida cedula (modulo 10) y RUC (modulo 11) para evitar las advertencias 59 y 62.

```php
use MTZ\Toolkit\XMLMaker\Validation\IdentificationValidator;

$id = new IdentificationValidator();

if (! $id->isValid($number, $identificationType)) {
    throw new RuntimeException('Identificacion del comprador invalida.');
}
```

## 3. Cuadre de totales

`XMLMaker` calcula los totales desde las lineas por defecto, por lo que el XML siempre cuadra (evita el error 52). Si envias los totales manualmente, puedes verificarlos con `TotalsCalculator`:

```php
use MTZ\Toolkit\XMLMaker\Calculation\TotalsCalculator;

$totals = (new TotalsCalculator())->calculate($details, tip: 0.0);

// Compara $totals->importeTotal con el total que vas a declarar
```

## Flujo recomendado

```php
$generated = (new XMLMaker())->generate($data);
$xml = $generated->toString();

$errors = (new XsdValidator())->validate($xml, $data->documentType);

if ($errors !== []) {
    // corrige antes de firmar y enviar
    return;
}

$signedXml = (new Signer($certPath, $certPassword))->loadXml($xml)->sign();
$result = $sender->send($generated->accessKey, $signedXml);
```

El [Pipeline](/modules/pipeline) ya ejecuta la validacion XSD como paso previo obligatorio: si el XML no es conforme, corta antes de firmar y devuelve `schemaErrors`.

## Errores del SRI

Cuando el SRI si responde con un rechazo, interpreta el codigo con `SriMessageCode`. Ver [Codigos de error](/reference/error-codes).
