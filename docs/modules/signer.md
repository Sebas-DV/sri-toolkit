# Signer

`Signer` firma XML SRI con una estructura XMLDSig/XAdES-BES compatible con el flujo implementado por el paquete.

## API principal

```php
use MTZ\Toolkit\Signer\Signer;

$signedXml = (new Signer(
    certificatePath: '/secure/path/certificate.p12',
    certificatePassword: getenv('SRI_CERTIFICATE_PASSWORD') ?: '',
))
    ->loadXml($xml)
    ->sign();
```

## Requisitos del XML

El documento debe:

- Ser XML valido.
- Tener nodo raiz.
- Incluir `id="comprobante"` o `Id="comprobante"` en el nodo raiz.
- No declarar namespaces en el nodo raiz.

Ejemplo valido:

```xml
<factura id="comprobante" version="1.1.0">
  <infoTributaria />
</factura>
```

`XMLMaker` genera el atributo requerido automaticamente.

## Certificado PKCS#12

El constructor recibe ruta y password:

```php
new Signer(
    certificatePath: '/secure/path/certificate.p12',
    certificatePassword: $password,
);
```

Internamente el loader:

- Verifica que el archivo exista y sea legible.
- Lee el contenedor PKCS#12 con `openssl_pkcs12_read`.
- Extrae llave privada, certificado, issuer, serial, modulo y exponente RSA.

## Resultado enriquecido

Si necesitas metadatos de la firma:

```php
$result = (new Signer($path, $password))
    ->loadXml($xml)
    ->signAsResult();

$result->xml;
$result->signatureId;
$result->signedAt;
```

## Configuracion

`SignerConfig` permite cambiar valores base:

```php
use MTZ\Toolkit\Signer\Config\SignerConfig;

$config = new SignerConfig(
    xmlVersion: '1.0',
    encoding: 'UTF-8',
    timeZone: 'America/Guayaquil',
    documentReferenceId: 'comprobante',
);
```

## Algoritmos

La implementacion usa identificadores XMLDSig/XAdES-BES con SHA-1 porque ese es el formato SRI-compatible implementado actualmente.

::: warning
No uses este signer como primitiva de firma general para protocolos nuevos. Esta orientado al formato de comprobantes SRI.
:::

## Errores comunes

| Excepcion | Causa probable |
| --- | --- |
| `CertificateException` | Archivo ilegible, password invalido o llave/certificado no encontrado |
| `InvalidXmlException` | XML vacio, invalido, sin `id="comprobante"` o con namespace en raiz |
| `SignerException` | Error al serializar, canonicalizar o firmar |
