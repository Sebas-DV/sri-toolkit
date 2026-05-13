<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Exceptions;

use RuntimeException;

final class AccessKeyException extends RuntimeException
{
    public static function missingField(string $field): self
    {
        return new self("The field [$field] is required.");
    }

    public static function invalidNumericField(string $field): self
    {
        return new self("The field [$field] must contain only numeric characters.");
    }

    public static function invalidLength(string $field, int $length, int $current): self
    {
        return new self("The field [$field] must be exactly {$length} characters long. $current given.");
    }

    public static function invalidSequential(): self
    {
        return new self("The sequential mus be numeric and cannot exceed 9 digits.");
    }

    public static function invalidDate(string $date): self
    {
        return new self("Invalid emission date: $date.");
    }

    public static function invalidAccessKeyBaseLength(int $current): self
    {
        return new self("The access key base must have 48 digits, $current given.");
    }
}