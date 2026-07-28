<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Enums;

/**
 * Authorization state returned by the SRI consultation web service (§8).
 */
enum ConsultationStatus: string
{
    case Authorized = 'AUTORIZADO';
    case NotAuthorized = 'NO AUTORIZADO';
    case PendingAnnulment = 'PENDIENTE DE ANULAR';
    case Annulled = 'ANULADO';

    /** Returned in estadoConsulta when the access key is out of range or not found. */
    case Rejected = 'RECHAZADA';
}
