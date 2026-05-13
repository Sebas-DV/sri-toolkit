<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Enums;

enum Environment: string
{
    case Testing = '1';
    case Production = '2';
}