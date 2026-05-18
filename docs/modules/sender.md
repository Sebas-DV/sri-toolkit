# Sender

`Sender` envia XML firmado a los Web Services SOAP del SRI y devuelve resultados tipados.

## API principal

```php
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\Environment;
use MTZ\Toolkit\Sender\Sender;

$sender = new Sender(
    config: new SenderConfig(
        environment: Environment::Testing,
    ),
);

$result = $sender->send($accessKey, $signedXml);
```

`send()` ejecuta dos pasos:

1. `validate($signedXml)`: recepcion del comprobante.
2. `authorize($accessKey)`: autorizacion, solo si la recepcion fue exitosa.

## Ambientes

```php
Environment::Testing;
Environment::Production;
```

Los WSDL usados por defecto son los endpoints oficiales de SRI para recepcion y autorizacion offline.

## Configuracion

```php
$config = new SenderConfig(
    environment: Environment::Testing,
    maxAttempts: 5,
    retryDelay: 1,
    sendDelay: 3,
    soapOptions: [
        'trace' => 0,
    ],
);
```

| Opcion | Default | Descripcion |
| --- | --- | --- |
| `environment` | `Testing` | Ambiente SRI |
| `maxAttempts` | `5` | Intentos de autorizacion |
| `retryDelay` | `1` | Segundos entre intentos de autorizacion |
| `sendDelay` | `3` | Espera entre recepcion y autorizacion |
| `soapOptions` | `[]` | Opciones adicionales para `SoapClient` |

Opciones SOAP base:

```php
[
    'trace' => 1,
    'cache_wsdl' => WSDL_CACHE_NONE,
    'user_agent' => 'MTZ/Toolkit',
    'connection_timeout' => 180,
    'exceptions' => true,
]
```

En produccion considera usar `trace => 0` para reducir riesgo de exponer XML o respuestas SOAP en logs de depuracion.

## Resultado de envio

```php
if ($result->success) {
    $authorizedDocument = $result->authorizationResult?->authorizedDocument;
    $authorizedXml = $authorizedDocument?->xml;
}

if (! $result->success) {
    $error = $result->error;
}
```

`SendResult::toArray()` devuelve una estructura serializable con recepcion, autorizacion y error.

## Usar pasos separados

```php
$reception = $sender->validate($signedXml);

if ($reception->success) {
    $authorization = $sender->authorize($accessKey);
}
```

## Validacion de clave

`authorize()` exige una clave de acceso numerica de 49 digitos. Si no cumple, lanza `InvalidAccessKeyException`.

## Testing

`Sender` acepta `SoapClientFactoryInterface` y `SleeperInterface`, lo que permite reemplazar red y esperas en tests.
