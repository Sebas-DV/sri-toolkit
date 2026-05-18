<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Enums;

/**
 * Represents the possible reception statuses returned by the SRI web service.
 */
enum ReceptionStatus: string
{
    case Received = 'RECIBIDA';
    case Returned = 'DEVUELTA';
    case Error = 'ERROR';
}
