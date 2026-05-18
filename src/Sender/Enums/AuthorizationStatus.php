<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Enums;

/**
 * Represents the possible authorization statuses returned by the SRI web service.
 */
enum AuthorizationStatus: string
{
    case Authorized = 'AUTORIZADO';
    case NotAuthorized = 'NO AUTORIZADO';
    case Processing = 'EN PROCESO';
    case Error = 'ERROR';
}
