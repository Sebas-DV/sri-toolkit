# Instalacion

## Instalar el paquete

```bash
composer require matiz-studio-creative/sri-toolkit
```

## Requisitos de PHP

El paquete requiere PHP 8.2 o superior:

```json
{
  "require": {
    "php": ">=8.2"
  }
}
```

Tambien requiere estas extensiones:

| Extension | Uso |
| --- | --- |
| `ext-soap` | Cliente SOAP para los WSDL del SRI |
| `ext-openssl` | Lectura PKCS#12 y firma criptografica |
| `ext-dom` | Construccion, lectura y canonicalizacion XML |
| `ext-libxml` | Parser XML usado por DOM |

En Linux, los paquetes suelen estar separados por version de PHP. Por ejemplo:

```bash
sudo apt install php8.2-soap php8.2-xml
```

OpenSSL suele estar incluido en la instalacion base de PHP, pero verifica que este habilitado:

```bash
php -m | grep -E "openssl|soap|dom|libxml"
```

## Instalar para desarrollo

```bash
git clone https://github.com/Sebas-DV/sri-toolkit.git
cd sri-toolkit
composer install
pnpm install
```

## Validar instalacion

```bash
composer test
composer stan
pnpm docs:build
```

## Certificados

Para firmar comprobantes reales necesitas un certificado PKCS#12 (`.p12` o `.pfx`) y su password.

Buenas practicas:

- Guarda el certificado fuera del repositorio.
- No subas certificados reales a fixtures, issues o artefactos de CI.
- Lee el password desde variables de entorno o un gestor de secretos.
- Restringe permisos del archivo al usuario que firma.

```php
$certificatePath = '/secure/path/certificate.p12';
$certificatePassword = getenv('SRI_CERTIFICATE_PASSWORD') ?: '';
```
