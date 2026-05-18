<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Support;

use MTZ\Toolkit\AccessKeyGenerator\Exceptions\AccessKeyException;

/**
 * Utility class for validating and formatting numeric strings used in access key construction.
 */
final class NumericString
{
    /**
     * Validates that the given value is a numeric string with exactly the specified length.
     *
     * @param string|int $value The value to validate.
     * @param int $length The required exact length.
     * @param string $field The field name (used in exception messages).
     * @return string The validated numeric string.
     * @throws AccessKeyException When the value is non-numeric or has the wrong length.
    */
    public static function fixed(string|int $value, int $length, string $field): string
    {
        $value = trim((string) $value);

        if (! ctype_digit($value))
        {
            throw AccessKeyException::invalidNumericField($field);
        }

        $currentLength = strlen($value);

        if ($currentLength !== $length)
        {
            throw AccessKeyException::invalidLength($field, $length, $currentLength);
        }

        return $value;
    }

    /**
     * Validates that the given value is numeric and pads it with leading zeros to the specified length.
     *
     * @param string|int $value The value to pad.
     * @param int $length The target length after padding.
     * @param string $field The field name (used in exception messages).
     * @return string The zero-padded numeric string.
     * @throws AccessKeyException When the value is non-numeric or exceeds the target length.
     */
    public static function padded(string|int $value, int $length, string $field): string
    {
        $value = trim((string) $value);

        if (! ctype_digit($value))
        {
            throw AccessKeyException::invalidNumericField($field);
        }

        if (strlen($value) > $length)
        {
            throw AccessKeyException::invalidLength($field, $length, strlen($value));
        }

        return str_pad($value, $length, '0', STR_PAD_LEFT);
    }
}
