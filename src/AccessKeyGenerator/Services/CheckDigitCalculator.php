<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Services;

use MTZ\Toolkit\AccessKeyGenerator\Exceptions\AccessKeyException;

final class CheckDigitCalculator
{
    private const MODULE = 11;
    private const MIN_MULTIPLIER = 2;
    private const MAX_MULTIPLIER = 7;

    public function calculate(string $keyValue): int
    {
        if (! ctype_digit($keyValue))
        {
            throw AccessKeyException::invalidNumericField('key_value');
        }

        $length = strlen($keyValue);

        if ($length !== 48)
        {
            throw AccessKeyException::invalidAccessKeyBaseLength($length);
        }

        $sum = 0;
        $multiplier = self::MIN_MULTIPLIER;

        for ($index = $length - 1; $index >= 0; $index--)
        {
            $sum += ((int) $keyValue[$index] * $multiplier);

            $multiplier++;

            if ($multiplier > self::MAX_MULTIPLIER)
            {
                $multiplier = self::MIN_MULTIPLIER;
            }
        }

        $result = self::MODULE - ($sum % self::MODULE);

        return match ($result)
        {
            11 => 0,
            10 => 1,
            default => $result,
        };
    }
}
