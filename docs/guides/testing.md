# Testing

El paquete esta disenado para probar el flujo sin depender de los servicios reales del SRI.

## Comandos

```bash
composer test
composer stan
composer cs:test
composer rector:test
composer analyze
```

`composer analyze` ejecuta CS Fixer en modo check, Rector en dry-run, PHPUnit y PHPStan.

## Tests existentes

| Carpeta | Proposito |
| --- | --- |
| `tests/Unit` | Reglas aisladas por servicio |
| `tests/Integration` | Firma y certificados generados temporalmente |
| `tests/Feature` | API publica y workflow completo |
| `tests/Support` | Fakes y factories de pruebas |

## Probar Sender sin red

`Sender` permite inyectar un factory SOAP falso:

```php
$fakeSoapClient = new FakeSoapClient(
    receptionResponses: [
        receptionResponse('RECIBIDA'),
    ],
    authorizationResponses: [
        authorizationResponse('AUTORIZADO'),
    ],
);

$sender = new Sender(
    config: new SenderConfig(maxAttempts: 1, sendDelay: 0),
    responseParser: new ResponseParser(),
    soapClientFactory: new FakeSoapClientFactory($fakeSoapClient),
    sleeper: new FakeSleeper(),
);
```

Esto evita llamadas a internet y elimina esperas reales.

## Probar Signer sin certificado real

Los tests de integracion generan certificados temporales con `TemporaryCertificateFactory`.

Para pruebas unitarias de estructura XAdES se puede usar:

- `FakeSignatureEngine`
- `FakeClock`
- `FakeIdGenerator`

Asi se obtienen IDs y fechas deterministicas.

## Documentacion

Para validar el sitio:

```bash
pnpm docs:build
```

Para desarrollo local:

```bash
pnpm docs:dev
```
