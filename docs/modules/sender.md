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

## Consulta de estado

`queryStatus()` consulta el estado actual de un comprobante en el Web Service de consulta (`ConsultaComprobante`), independiente del flujo de envio. Util para conciliacion y para detectar comprobantes anulados.

```php
$consulta = $sender->queryStatus($accessKey);

$consulta->status;         // ConsultationStatus
$consulta->isAuthorized(); // true si esta AUTORIZADO
$consulta->isAnnulled();   // true si esta ANULADO
$consulta->documentType;   // tipoComprobante
$consulta->issuerRuc;      // rucEmisor
$consulta->authorizationDate;
```

Estados posibles: `AUTORIZADO`, `NO AUTORIZADO`, `PENDIENTE DE ANULAR`, `ANULADO`, y `RECHAZADA` cuando la clave esta fuera de rango o no existe.

## Envio por lote

`sendBatch()` arma el XML `<lote>`, lo envia a recepcion y luego autoriza el lote por su clave. Limite: 50 comprobantes o 500 kB por lote.

```php
$result = $sender->sendBatch($loteAccessKey, '1790012345001', [$signedXmlA, $signedXmlB]);

$result->success;                              // recibido y todos autorizados
$result->authorizationResult->authorizations; // resultado por comprobante (list<AuthorizationResult>)
```

La clave de lote se genera igual que la de un comprobante: usa el [AccessKeyGenerator](/modules/access-key-generator) con la serie y secuencial del lote. Si el lote esta vacio o excede los limites, lanza `BatchException`.

## Codigos de mensaje tipados

`SriMessageCode` convierte el identificador de cada mensaje SRI en un enum con clasificacion, para reaccionar por codigo en vez de texto.

```php
foreach ($result->authorizationResult?->messages ?? [] as $message) {
    $code = $message->sriCode(); // ?SriMessageCode

    if ($code?->isProcessing()) {
        // codigo 70: esperar, no reenviar
    }

    if ($code?->isImpediment()) {
        // RUC/establecimiento clausurado, inactivo: resolver antes de reenviar
    }

    if ($code?->isRetryable()) {
        // corregir y reenviar con la misma clave
    }
}
```

## Testing

`Sender` acepta `SoapClientFactoryInterface` y `SleeperInterface`, lo que permite reemplazar red y esperas en tests.
