<?php

declare(strict_types=1);

namespace MTZ\Toolkit\XMLMaker\Exceptions;

/**
 * Raised when an official SRI schema cannot be located for validation.
 */
final class SchemaException extends XmlGeneratorException
{
    /**
     * @param string $path The expected schema path that could not be found.
     * @return self
     */
    public static function missing(string $path): self
    {
        return new self(sprintf('SRI schema not found at "%s".', $path));
    }
}
