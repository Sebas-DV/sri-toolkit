<?php

declare(strict_types=1);

namespace MTZ\Toolkit\Signer\Contract;

/**
 * Abstraction for generating unique identifiers used in XML signatures.
 */
interface IdGeneratorInterface
{
    /**
     * Generate a unique identifier string.
     *
     * @return string A unique ID.
     */
    public function generate(): string;
}
