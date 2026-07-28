# Codigos de error y advertencia

El SRI devuelve mensajes con un identificador numerico en recepcion, autorizacion y consulta. `SriMessageCode` los expone como enum tipado con clasificacion, para reaccionar por codigo en vez de por texto.

## Uso

```php
use MTZ\Toolkit\Sender\Enums\SriMessageCode;

foreach ($result->authorizationResult?->messages ?? [] as $message) {
    $code = $message->sriCode(); // ?SriMessageCode

    $code?->description();   // texto legible
    $code?->isWarning();     // advertencia (no rechazo)
    $code?->isProcessing();  // codigo 70: esperar, no reenviar
    $code?->isImpediment();  // resolver antes de reenviar
    $code?->isRetryable();   // corregir y reenviar con la misma clave
}
```

## Clasificacion

- **Reenviable** (`isRetryable`): corrige el error y reenvia con la misma clave y secuencial.
- **Impedimento** (`isImpediment`): RUC o establecimiento clausurado, RUC inactivo o cerrado; se debe resolver primero.
- **En procesamiento** (`isProcessing`): codigo 70; no reenvies, espera la respuesta.
- **Advertencia** (`isWarning`): informativa, no rechaza el comprobante.

## Errores

| Codigo | Caso | Etapa | Clasificacion |
| --- | --- | --- | --- |
| 2 | RUC del emisor no activo | Autorizacion | Impedimento |
| 10 | Establecimiento clausurado | Autorizacion | Impedimento |
| 26 | Tamano maximo superado | Recepcion | Reenviable |
| 27 | Clase de contribuyente no permitida | Autorizacion | Impedimento |
| 28 | Acuerdo de medios electronicos no aceptado | Recepcion | Reenviable |
| 35 | Documento invalido (no pasa el esquema) | Recepcion | Reenviable |
| 36 | Version de esquema descontinuada | Recepcion | Reenviable |
| 37 | RUC sin autorizacion de emision | Autorizacion | Impedimento |
| 39 | Firma invalida | Autorizacion | Reenviable |
| 40 | Error en el certificado | Autorizacion | Reenviable |
| 43 | Clave de acceso ya registrada | Recepcion | Reenviable |
| 45 | Secuencial ya registrado | Recepcion | Reenviable |
| 46 | RUC no existe | Autorizacion | Impedimento |
| 47 | Tipo de comprobante no existe | Recepcion | Reenviable |
| 48 | Esquema XSD no existe | Recepcion | Reenviable |
| 49 | Argumentos nulos al WS | Recepcion | Reenviable |
| 50 | Error interno general | Recepcion | Reenviable |
| 52 | Error en diferencias (calculos) | Autorizacion | Reenviable |
| 56 | Establecimiento cerrado | Autorizacion | Impedimento |
| 57 | Autorizacion suspendida | Autorizacion | Impedimento |
| 58 | Error en la estructura de la clave de acceso | Autorizacion | Reenviable |
| 63 | RUC clausurado | Autorizacion | Impedimento |
| 65 | Fecha de emision extemporanea | Emisor/Recepcion | Reenviable |
| 67 | Fecha invalida | Recepcion | Reenviable |
| 70 | Clave de acceso en procesamiento | Recepcion | En procesamiento |
| 80 | Error de estructura de clave (consulta) | Autorizacion | Reenviable |
| 82 | Error en la fecha de inicio de transporte | Recepcion | Reenviable |
| 92 | Error al validar monto de devolucion del IVA | Recepcion | Reenviable |

## Advertencias

| Codigo | Caso |
| --- | --- |
| 59 | Identificacion del adquirente no existe |
| 60 | Ambiente de pruebas/certificacion |
| 62 | Identificacion del adquirente incorrecta |

Notas del SRI: un comprobante rechazado (salvo impedimentos) puede reenviarse una vez corregido sin generar nueva clave ni secuencial. Ante el codigo 70 no se debe reenviar hasta recibir autorizacion o rechazo, en un maximo de 24 horas.
