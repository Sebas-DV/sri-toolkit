<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Enums;

/**
 * Represents the target SRI environment: testing or production.
 */
enum Environment: string
{
    case Testing = 'testing';
    case Production = 'production';
}
