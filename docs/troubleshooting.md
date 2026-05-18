# Troubleshooting

## `Class "SoapClient" not found`

La extension SOAP no esta habilitada.

Verifica:

```bash
php -m | grep soap
```

Instala la extension correspondiente a tu version de PHP.

## `The certificate file [...] is unreadable`

La ruta del certificado no existe o el proceso PHP no tiene permisos de lectura.

Revisa:

- Ruta absoluta del archivo.
- Permisos del usuario que ejecuta PHP.
- Que el certificado no este dentro de un contenedor o volumen distinto.

## `The certificate password is invalid`

OpenSSL no pudo leer el PKCS#12 con el password dado. Verifica que:

- El password corresponde al archivo.
- No hay espacios extra al leer la variable de entorno.
- El archivo no esta corrupto.

## `The XML document must contain the [comprobante] reference id`

El nodo raiz no contiene `id="comprobante"` ni `Id="comprobante"`.

Ejemplo:

```xml
<factura id="comprobante" version="2.1.0">
```

Si generas XML con `XMLMaker`, este atributo se agrega automaticamente.

## `The XML root element cannot contain namespaces`

El signer valida que la raiz no tenga declaraciones `xmlns`.

Mueve namespaces a los nodos internos o genera el XML con `XMLMaker`.

## `Invalid response from WebService SRI`

El cliente SOAP devolvio una respuesta no esperada. Puede ocurrir por:

- Cambios o indisponibilidad temporal del SRI.
- WSDL no disponible.
- Error de red.
- Opciones SOAP personalizadas incompatibles.

Consulta `ReceptionResult::rawResponse` o `AuthorizationResult::rawResponse` en ambientes controlados, evitando loggear datos sensibles en produccion.

## `Invalid access key`

`authorize()` requiere una clave numerica de 49 digitos.

```php
preg_match('/^\d{49}$/', $accessKey);
```

## Build de documentacion falla por links

Ejecuta:

```bash
pnpm docs:build
```

Revisa que los links internos usen rutas existentes, por ejemplo `/modules/signer` o `/guides/complete-workflow`.
