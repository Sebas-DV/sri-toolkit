<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Enums;

enum XmlEnvironment: string
{
    case Testing = '1';
    case Production = '2';
}
