<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Enums;

/**
 * SRI environment identifiers (testing or production).
 */
enum Environment: string
{
    case Testing = '1';
    case Production = '2';
}
