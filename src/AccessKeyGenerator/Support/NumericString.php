<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Support;

use MTZ\Toolkit\AccessKeyGenerator\Exceptions\AccessKeyException;

final class NumericString
{
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