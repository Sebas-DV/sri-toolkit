<?php

declare(strict_types=1);

namespace MTZ\Toolkit\AccessKeyGenerator\Services;

use MTZ\Toolkit\AccessKeyGenerator\Exceptions\AccessKeyException;

/**
 * Calculates the SRI access key check digit using the modulo 11 algorithm.
 *
 * Starting from the rightmost digit with multiplier 2, each digit is multiplied
 * by a cycling factor (2–7). The sum modulo 11 determines the final check digit.
 */
final class CheckDigitCalculator
{
    /** @var int The modulo base for check digit calculation. */
    private const MODULE = 11;

    /** @var int The minimum multiplier used in the digit-factor cycle. */
    private const MIN_MULTIPLIER = 2;

    /** @var int The maximum multiplier used in the digit-factor cycle. */
    private const MAX_MULTIPLIER = 7;

    /**
     * Calculates the check digit for a given 48-digit access key base.
     *
     * @param string $keyValue The 48-digit access key base string.
     * @return int The computed check digit (0–9).
     * @throws AccessKeyException When the key value is non-numeric or not 48 digits.
     */
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
