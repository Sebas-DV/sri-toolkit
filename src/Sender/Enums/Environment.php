<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Sender\Enums;

enum Environment: string
{
    case Testing = 'testing';
    case Production = 'production';
}