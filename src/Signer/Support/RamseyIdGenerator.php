<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Support;

use MTZ\Toolkit\Signer\Contract\IdGeneratorInterface;
use Ramsey\Uuid\Uuid;

/**
 * UUID-based ID generator using the Ramsey UUID library.
 *
 * Generates random UUID v4 strings for use as XML signature identifiers.
 */
final class RamseyIdGenerator implements IdGeneratorInterface
{
    /**
     * Generate a UUID v4 string.
     *
     * @return string A UUID v4 identifier.
     */
    public function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}
