# Certificates y Security

Este modulo guarda de forma segura el certificado PKCS#12 y su clave, cifrando la contrasena en reposo con AES-256-GCM antes de persistirla en el [Storage](/modules/storage).

## Cifrado de cadenas

`OpenSslStringEncrypter` implementa `StringEncrypterInterface` (`encrypt`, `decrypt`) con AES-256-GCM autenticado. La llave debe tener 32 bytes.

```php
use MTZ\Toolkit\Security\OpenSslStringEncrypter;

$encrypter = OpenSslStringEncrypter::fromBase64Key($base64Key); // 32 bytes en base64
$cipher = $encrypter->encrypt('mi-clave');   // mtz:v1:...
$plain  = $encrypter->decrypt($cipher);
```

El formato de salida es `mtz:v1:` mas un JSON en base64 con `cipher`, `iv`, `tag` y `value`. Guarda la llave fuera del repositorio, en un gestor de secretos o variable de entorno protegida.

## Guardar un certificado

`StorageCertificateRepository` cifra la contrasena y guarda el `.p12` y sus metadatos en el storage.

```php
use MTZ\Toolkit\Certificates\StorageCertificateRepository;

$repository = new StorageCertificateRepository(
    storage: $storage,        // DocumentStorageInterface (local o S3)
    encrypter: $encrypter,    // StringEncrypterInterface
);

$stored = $repository->store(
    ownerKey: '1790012345001',
    certificateContents: file_get_contents('/secure/certificate.p12'),
    password: getenv('SRI_CERTIFICATE_PASSWORD') ?: '',
    alias: 'produccion',
    expiresAt: new DateTimeImmutable('2027-01-01'),
);
```

La contrasena nunca se guarda en texto plano: `StoredCertificate::$encryptPassword` contiene el valor cifrado.

## Resolver el material para firmar

`CertificateMaterialResolver` descifra la contrasena y devuelve la ruta y clave listas para el [Signer](/modules/signer).

```php
use MTZ\Toolkit\Certificates\CertificateMaterialResolver;

$resolver = new CertificateMaterialResolver(
    storage: $storage,
    encrypter: $encrypter,
);

$material = $resolver->resolve('1790012345001');

$material->path;      // ruta al .p12
$material->password;  // contrasena en claro (en memoria)
```

## Otras operaciones

```php
$repository->get('1790012345001');     // StoredCertificate (metadatos)
$repository->delete('1790012345001');  // elimina certificado y metadatos
```

## Buenas practicas

- Guarda la llave de cifrado y los certificados fuera del control de versiones y del web root.
- Usa un `ownerKey` estable por emisor (por ejemplo el RUC).
- No registres en logs la contrasena resuelta ni el XML firmado.
