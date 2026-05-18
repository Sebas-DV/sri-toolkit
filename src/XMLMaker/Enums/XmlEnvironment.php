<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Enums;

/**
 * Enumeration of SRI target environments.
 *
 * '1' for testing/pruebas, '2' for production/producción.
 */
enum XmlEnvironment: string
{
    case Testing = '1';
    case Production = '2';
}
