# Consulta de estado y envio por lote

## Consultar el estado de un comprobante

Independiente del flujo de envio, consulta el estado actual (util para conciliacion y para detectar anulados).

```php
use MTZ\Toolkit\Sender\Config\SenderConfig;
use MTZ\Toolkit\Sender\Enums\Environment;
use MTZ\Toolkit\Sender\Sender;

$sender = new Sender(new SenderConfig(environment: Environment::Production));

$consulta = $sender->queryStatus($accessKey);

if ($consulta->isAuthorized()) {
    // AUTORIZADO
}

if ($consulta->isAnnulled()) {
    // ANULADO
}

$consulta->status;             // ConsultationStatus
$consulta->documentType;       // tipoComprobante
$consulta->issuerRuc;          // rucEmisor
$consulta->authorizationDate;  // fecha de autorizacion
```

Estados: `AUTORIZADO`, `NO AUTORIZADO`, `PENDIENTE DE ANULAR`, `ANULADO`, `RECHAZADA`.

## Enviar un lote

Un lote agrupa hasta 50 comprobantes firmados (maximo 500 kB). Se envia a recepcion y luego se autoriza por su clave de lote.

```php
$loteAccessKey = (new AccessKeyGenerator())->generate(
    AccessKeyData::make(
        emissionDate: '2026-05-13',
        documentType: DocumentType::Invoice,
        ruc: '1790012345001',
        environment: AccessKeyEnvironment::Production,
        sequential: 100,           // serie/secuencial del lote
        establishmentCode: '001',
        emissionPointCode: '001',
    ),
);

$result = $sender->sendBatch($loteAccessKey, '1790012345001', [$signedXmlA, $signedXmlB]);

if ($result->success) {
    foreach ($result->authorizationResult->authorizations as $authorization) {
        $authorization->success;
        $authorization->authorizedDocument?->xml;
    }
}
```

Si el lote esta vacio o excede los limites, `sendBatch()` lanza `BatchException`.

## Interpretar mensajes

Usa `SriMessageCode` para reaccionar por codigo. Ver [Codigos de error](/reference/error-codes).
