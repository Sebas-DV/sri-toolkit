<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Enums;

enum ReceptionStatus: string
{
    case Received = 'RECIBIDA';
    case Returned = 'DEVUELTA';
    case Error = 'ERROR';
}