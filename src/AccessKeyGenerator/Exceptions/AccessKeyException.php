<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Exceptions;

use RuntimeException;

/**
 * Exception thrown when access key validation or generation fails.
 *
 * Provides named factory methods for specific failure scenarios
 * (missing fields, invalid lengths, bad dates, etc.).
 */
final class AccessKeyException extends RuntimeException
{
    /**
     * Factory method to create an exception for a missing required field.
     *
     * @param string $field
     * @return self
     */
    public static function missingField(string $field): self
    {
        return new self("The field [$field] is required.");
    }

    /**
     * Factory method to create an exception for an invalid numeric field.
     *
     * @param string $field
     * @return self
     */
    public static function invalidNumericField(string $field): self
    {
        return new self("The field [$field] must contain only numeric characters.");
    }

    /**
     * Factory method to create an exception for an invalid length of a field.
     *
     * @param string $field
     * @param int $length
     * @param int $current
     * @return self
     */
    public static function invalidLength(string $field, int $length, int $current): self
    {
        return new self("The field [$field] must be exactly {$length} characters long. $current given.");
    }

    /**
     * Factory method to create an exception for an invalid sequential number.
     *
     * @return self
     */
    public static function invalidSequential(): self
    {
        return new self('The sequential mus be numeric and cannot exceed 9 digits.');
    }

    /**
     * Factory method to create an exception for an invalid emission date.
     *
     * @param string $date
     * @return self
     */
    public static function invalidDate(string $date): self
    {
        return new self("Invalid emission date: $date.");
    }

    /**
     * Factory method to create an exception for an invalid length of the access key base.
     *
     * @param int $current
     * @return self
     */
    public static function invalidAccessKeyBaseLength(int $current): self
    {
        return new self("The access key base must have 48 digits, $current given.");
    }
}
